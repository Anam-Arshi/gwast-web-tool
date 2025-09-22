library(R.utils)      # for gunzip()
library(data.table)
library(meta)
library(httr)
library(jsonlite)
library(logger)
library(Rcpp)
library(future.apply)




# C++ function for meta-analysis
Rcpp::sourceCpp("meta_analysis_ivw.cpp")


# Configure parallel processing
num_cores <- min(40, availableCores() - 5)  # Use up to 95 cores, leave one free
plan(multicore, workers = num_cores)
options(future.globals.maxSize = 9000 * 1024^2)  # Set to 8GB for large data handling

# Function to load and merge all study files
load_study_data <- function(file) {
  tryCatch({
    df <- fread(file)

required_cols <- c("beta", "standard_error", "p_value", "chromosome", "base_pair_location")
present_id_cols <- c("variant_id", "rsid", "snpid") # Columns that can serve as an ID

# Check for presence of required non-ID columns
missing_required <- setdiff(required_cols, names(df))
if (length(missing_required) > 0) {
  stop(sprintf("Missing required columns in %s: %s", file, paste(missing_required, collapse = ", ")))
}

# Check for presence of at least one ID column
if (!any(present_id_cols %in% names(df))) {
  stop(sprintf("Missing a required identifier column (either 'variant_id' or 'rsid') in %s", file))
}

# Rename 'variant_id' to 'rsid' if 'rsid' is not present but 'variant_id' is
if ("variant_id" %in% names(df) && !("rsid" %in% names(df))) {
  setnames(df, "variant_id", "rsid")
}
# If both are present, no renaming is needed, both columns will remain.

    # Ensure proper types
    df[, beta := as.numeric(beta)]
    df[, standard_error := as.numeric(standard_error)]
    df[, p_value := as.numeric(p_value)]
    df <- df[df$rsid != "#NA", ]

    # Log the number of rows and columns
    log_info(sprintf("Loaded %d rows and %d columns from %s", nrow(df), ncol(df), file))

    df <- df[!is.na(rsid) & !is.na(beta) & !is.na(standard_error)]  # Filter NAs
    setnames(df, c("beta", "standard_error"), paste0(c("beta_", "se_"), file))
    return(df)
  }, error = function(e) {
    log_error(sprintf("Error loading file %s: %s", file, e$message))
    return(NULL)
  })
}

# Function to process a batch of SNPs
process_snp_batch <- function(batch_data) {
  results <- lapply(seq_len(nrow(batch_data)), function(i) {
    snp_data <- batch_data[i, ]

    # Extract beta and standard errors
    beta_cols <- grep("^beta_", names(snp_data), value = TRUE)
    se_cols <- grep("^se_", names(snp_data), value = TRUE)

    beta <- unlist(snp_data[, ..beta_cols], use.names = FALSE)
    ses  <- unlist(snp_data[, ..se_cols], use.names = FALSE)

    valid_indices <- !is.na(beta) & !is.na(ses) & ses > 0

    if (sum(valid_indices) > 1) {
      beta <- beta[valid_indices]
      ses <- ses[valid_indices]

      res <- tryCatch({
       meta_analysis_ivw(beta, ses, tau2_method, run_fixed, run_random)

      }, error = function(e) {
        log_error("Rcpp meta-analysis failed for rsid %s: %s", snp_data$rsid, e$message)
        return(NULL)
      })

      if (is.null(res) || any(is.na(res$beta_fixed), is.na(res$se_fixed), is.na(res$p_value_fixed))) {
        log_warn("Invalid result for rsid %s", snp_data$rsid)
        return(NULL)
      }
      return(data.table(
        rsid = snp_data$rsid,
        n_studies = sum(valid_indices),

        # Fixed effect
        OR_fixed = exp(res$beta_fixed),
        z_score_fixed = res$z_score_fixed,
        p_value_fixed = res$p_value_fixed,
        ci_fixed = sprintf("%.4f–%.4f", exp(res$ci_lower_fixed), exp(res$ci_upper_fixed)),

        # Random effect
        OR_random = exp(res$beta_random),
        z_score_random = res$z_score_random,
        p_value_random = res$p_value_random,
        ci_random = sprintf("%.4f–%.4f", exp(res$ci_lower_random), exp(res$ci_upper_random)),

        # Heterogeneity
        tau2 = res$tau2,
        tau = sqrt(res$tau2),
        I2 = res$I2,
        H = res$H,
        Q_statistic = res$Q,
        Q_p_value = res$pval_Q
      ))


    } else {
      log_warn("Skipping rsid %s: only %d valid entries", snp_data$rsid, sum(valid_indices))
      return(NULL)
    }
  })

  return(rbindlist(results[!sapply(results, is.null)], fill = TRUE))
}

# Main execution function
main <- function() {

# Set to 20GB or higher based on your system memory
options(future.globals.maxSize = 10000 * 1024^2)  # 10 GB

  log_info("Starting meta-analysis pipeline")
  
    # Get job folder from command line arguments
  args <- commandArgs(TRUE)
  folderN <- args[1]
  cat("Working directory: ", paste0("user_uploads/", folderN), "\n")

  # Set working directory to user_uploads/<job>
  setwd(paste0("user_uploads/", folderN))

  # Read meta_input.json
  meta_input_path <- file.path(getwd(), "meta_input.json")
  if (!file.exists(meta_input_path)) stop("meta_input.json not found in working directory")

  meta_data <- fromJSON(meta_input_path)

  # Extract model parameters from JSON
  model <- meta_data$model
  run_fixed <- isTRUE(model$fixed)
  run_random <- isTRUE(model$random)
  tau2_method <- if (run_random) meta_data$tau2 else "DL"

  # Log selected model info
  log_info(sprintf("Model selected: fixed = %s, random = %s, tau2 method = %s", run_fixed, run_random, tau2_method))

  # Extract target build
  target_build <- meta_data$target_build

  # Prepare file paths for meta-analysis input files
  files_info <- meta_data$files

  # print(str(files_info))


  harm_dir <- file.path(getwd(), "harmonized_files")
  orig_dir <- getwd()

file_names <- sapply(seq_len(nrow(files_info)), function(i) {
  fname <- files_info$filename[i]
  harmonize <- isTRUE(files_info$harmonize[i])

  if (harmonize) {
    hf <- file.path(harm_dir, paste0(tools::file_path_sans_ext(fname), "_harmonized.ssf.tsv.gz"))
    if (file.exists(hf)) return(hf)
  }
  op <- file.path(orig_dir, paste0(tools::file_path_sans_ext(fname), "_processed.ssf.tsv.gz"))
  if (file.exists(op)) return(op)

  stop(paste0("File not found: ", fname))
})

  if (length(file_names) == 0) stop("No .gz files found in the directory")

  # Extract to .tsv files (skip if already exists)
  log_info("Extracting .tsv.gz files")
  tsv_files <- sapply(file_names, function(gz_file) {
    gz_dir <- dirname(gz_file)
    tsv_path <- file.path(gz_dir, sub("\\.gz$", "", basename(gz_file)))
    
    if (!file.exists(tsv_path)) {
      R.utils::gunzip(gz_file, destname = tsv_path, overwrite = FALSE, remove = FALSE)
    } else {
      log_info(paste("File already extracted:", tsv_path))
    }

    return(tsv_path)
  }, USE.NAMES = FALSE)

  # Load and merge data
  log_info("Loading and merging study data")
  study_data_list <- lapply(tsv_files, load_study_data)
  study_data_list <- study_data_list[!sapply(study_data_list, is.null)]


  if (length(study_data_list) < 2) {
    stop("Not enough valid study files to perform meta-analysis")
  }

# log_info("Identifying SNPs with p < 0.05 in at least one study")

# # Get rsIDs with p < 0.05 in any study
# significant_rsid <- unique(unlist(
#   lapply(study_data_list, function(df) df$rsid[df$p_value < 0.05])
# ))

# # Filter all data frames to only include those rsIDs
# filtered_data_list <- lapply(study_data_list, function(df) {
#   df[df$rsid %in% significant_rsid, ]
# })

# for (i in seq_along(filtered_data_list)) {
#   cat("Study", i, ": Number of rows =", nrow(filtered_data_list[[i]]), 
#       ", Number of unique rsids =", length(unique(filtered_data_list[[i]]$rsid)), "\n")
# }

## 1. find rsids that occur in ≥2 studies
rsid_counts <- table(unlist(lapply(study_data_list, `[[`, "rsid")))
rsids_keep  <- names(rsid_counts[rsid_counts >= 2])

## 2. filter each study and merge
filtered_list <- lapply(study_data_list,
                        function(df) df[df$rsid %in% rsids_keep, ])

combined_data <- Reduce(function(x, y) merge(x, y, by = "rsid", all = TRUE),
                        filtered_list)

# print(colnames(combined_data))

# log_info("Merging datasets on SNPs significant in at least one study")
# combined_data <- Reduce(function(x, y) merge(x, y, by = "rsid", all = FALSE), study_data_list)
  
  if (nrow(combined_data) == 0) {
    stop("No overlapping variants found between studies")
  }
  
  log_info(sprintf("Found %d overlapping variants", nrow(combined_data)))
  
  # Calculate batch size based on the number of variants and available cores
  
# Process meta-analysis in chunks
chunk_size <- max(1000, ceiling(nrow(combined_data) / (num_cores * 20)))
chunks <- split(combined_data, ceiling(seq_len(nrow(combined_data)) / chunk_size))

log_info(sprintf("Processing %d variants in %d chunks using %d cores", 
                 nrow(combined_data), length(chunks), num_cores))

# Process meta-analysis in batches using parallel processing
results <- future_lapply(seq_along(chunks), function(i) {
  
  # Try to process the chunk and catch any errors
  tryCatch({
    batch_results <- process_snp_batch(chunks[[i]])  # Pass the specific chunk
    if (is.null(batch_results) || nrow(batch_results) == 0) {
      log_warn(sprintf("Chunk %d produced no results", i))
    } else {
      log_info(sprintf("Chunk %d produced %d results", i, nrow(batch_results)))
    }
    return(batch_results)
    
  }, error = function(e) {
    log_error(sprintf("Error processing chunk %d: %s", i, e$message))
    return(NULL)  # Return NULL for this chunk
  })
}, future.seed = TRUE)

# Combine and save meta-analysis results
meta_results_df <- rbindlist(results, fill = TRUE)

if (nrow(meta_results_df) == 0) {
  stop("No results to merge. Please check the processing steps.")
}

annotation_cols <- c("rsid", "chromosome", "base_pair_location", "effect_allele", "other_allele", "variant_id")
variant_info <- filtered_list[[1]][, intersect(annotation_cols, names(filtered_list[[1]])), with = FALSE]

meta_results_df <- merge(meta_results_df, variant_info, by = "rsid", all.x = TRUE)


# Round numeric columns to 4 digits
# num_cols <- names(meta_results_df)[sapply(meta_results_df, is.numeric)]
# meta_results_df[, (num_cols) := lapply(.SD, function(x) round(x, 4)), .SDcols = num_cols]

meta_results_df[, I2 := sprintf("%.2f", I2 * 100)]

meta_results_df[, p_value_fixed := sprintf("%.4e", p_value_fixed)]
meta_results_df[, p_value_random := sprintf("%.4e", p_value_random)]
meta_results_df[, Q_p_value := sprintf("%.4e", Q_p_value)]

# Sort: p_value↑, I2↑, q_p_value↓
setorder(meta_results_df, p_value_fixed, I2, -Q_p_value)


meta_file <- "meta_results.tsv"
fwrite(meta_results_df, meta_file, sep = "\t", quote = FALSE, na = "NA")
log_info(sprintf("Results saved: %d variants", nrow(meta_results_df)))

# Save the top 10,000 rows to a new file
meta_file_top10k <- "meta_results_top_10k.tsv"
fwrite(meta_results_df[1:10000, ], meta_file_top10k, sep = "\t", quote = FALSE, na = "NA")
log_info(sprintf("Top 10K results saved: %d variants", min(10000, nrow(meta_results_df))))

}

# Run the analysis
main()
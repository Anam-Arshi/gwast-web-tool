args <- commandArgs(trailingOnly = TRUE)
job_id <- args[1]
setwd(paste0("user_uploads/", job_id))

library(data.table)
library(logger)

input_file <- "meta_leads.tsv"
if (!file.exists(input_file)) {
    stop("meta_leads.tsv not found.")
}

log_info("Reading meta_leads.tsv...")
meta_df <- fread(input_file)

# Rename for consistency
setnames(meta_df, c("rsID", "CHR", "POS", "EA", "NEA", "P", "OR"), 
                   c("rsid", "chromosome", "base_pair_location", 
                     "effect_allele", "other_allele", "p_value", "odds_ratio"), skip_absent = TRUE)

# Construct VEP input (fallback alleles if missing)
meta_df[, ref := ifelse(!is.na(other_allele) & other_allele != "", other_allele, "T")]
meta_df[, alt := ifelse(!is.na(effect_allele) & effect_allele != "", effect_allele, "A")]
meta_df[, vep_input := paste0(chromosome, "\t", base_pair_location, "\t.\t", ref, "\t", alt)]
fwrite(meta_df[, .(vep_input)], "vep_input.txt", col.names = FALSE, sep = "\t", quote = FALSE)

# Run VEP
vep_output <- "annotated.tsv"
vep_cmd <- paste(
  "vep",
  "-i vep_input.txt",
  "-o", vep_output,
  "--cache",
  "--offline",
  "--dir_cache /home/biomedinfo/.vep",
  "--assembly GRCh38",
  "--symbol",
  "--tab",
  "--force_overwrite",
  "--no_stats",
  "--pick",
  "--protein",
  "--canonical",
  "--fields",
  paste(
    "Uploaded_variation,Location,Allele,Consequence,Feature,Biotype,Protein_position,Amino_acids"
  )
)

log_info("Running VEP...")
system(vep_cmd)

# Merge VEP output if available
if (file.exists(vep_output)) {
    vep_df <- fread(vep_output, skip = "#Uploaded_variation", header = TRUE)
    setnames(vep_df, "#Uploaded_variation", "uploaded_variation")

    vep_df[, c("chr", "pos") := tstrsplit(Location, ":", fixed = TRUE)]
    vep_df[, pos := as.integer(pos)]
    vep_df[, chr := gsub("^chr", "", chr)]

    # Determine consequence type
    coding_keywords <- c("missense_variant", "synonymous_variant", "stop_gained", "frameshift_variant")
    vep_df[, consequence_type := ifelse(
        grepl(paste(coding_keywords, collapse = "|"), Consequence, ignore.case = TRUE),
        "Coding", "Non-coding"
    )]

    # Normalize meta_df chromosome
    meta_df[, chromosome := gsub("^chr", "", chromosome)]

    # Merge VEP annotations without overwriting gene
    merged_df <- merge(meta_df, vep_df,
                       by.x = c("chromosome", "base_pair_location"),
                       by.y = c("chr", "pos"),
                       all.x = TRUE)

    # Drop unnecessary columns
    merged_df[, c("vep_input", "uploaded_variation", "Location", "Allele") := NULL]

    # Save annotated output
    fwrite(merged_df, "meta_results_annotated.tsv", sep = "\t", quote = FALSE)
    log_info(sprintf("Annotation complete. Output saved: meta_results_annotated.csv (%d rows)", nrow(merged_df)))
} else {
    log_warn("VEP failed or output file not found.")
}

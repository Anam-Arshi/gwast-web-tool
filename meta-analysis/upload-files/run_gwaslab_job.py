#!/usr/bin/env python3
import os, sys, time, glob, zipfile, json
import matplotlib
matplotlib.use('Agg')
import gwaslab as gl
import pandas as pd
from concurrent.futures import ProcessPoolExecutor, as_completed


# -------- CONFIG --------
MAX_WORKERS_HDD = 10       # limit parallel jobs to avoid HDD thrashing
DEFAULT_GENOME_BUILD = "38"
DEFAULT_FILE_FORMAT = "tsv"
# ------------------------

job_id = sys.argv[1]
job_dir = f"../../user_uploads/{job_id}"
log_file = os.path.join(job_dir, "preprocess.log")
plots_dir = os.path.join(job_dir, "plots")
os.makedirs(plots_dir, exist_ok=True)
os.chmod(plots_dir, 0o777)

# --- Read file metadata from job_config.json ---
job_config_path = os.path.join(job_dir, "job_config.json")
if os.path.exists(job_config_path):
    with open(job_config_path) as jf:
        job_config = json.load(jf)
        files_metadata = job_config.get("files", [])
else:
    files_metadata = []
    # Optional: log("⚠ No job_config.json found, using defaults for all files.")

# Build mapping for quick lookup by filename
files_meta_map = {fmeta['filename']: fmeta for fmeta in files_metadata}

def log(msg):
    ts = time.strftime("[%Y-%m-%d %H:%M:%S]")
    with open(log_file, "a") as lf:
        lf.write(f"{ts} {msg}\n")
    print(msg, flush=True)


def process_file(fpath):
    filename = os.path.basename(fpath)  # keep extension for mapping
    base = filename.rsplit('.', 1)[0]

    # Get genome build and file format from job_config metadata, use defaults if missing
    file_meta = files_meta_map.get(filename, {})
    genome_build = file_meta.get("genome_build", DEFAULT_GENOME_BUILD)
    file_format = file_meta.get("format", DEFAULT_FILE_FORMAT)

    result = {"file": base, "out_files": [], "error": None}
    try:
        log(f"{base}: Reading file {fpath} (build {genome_build}, format {file_format})")

        # Use format info in Sumstats constructor if supported, otherwise fallback to "auto"
        fmt_arg = file_format if file_format != "" else "auto"
        ss = gl.Sumstats(fpath, fmt=fmt_arg, build=genome_build)

        ss.basic_check(n_cores=3)
        log(f"{base}: Basic checks passed, plotting initial data")
        
        if not (('SNPID' in ss.data.columns) and ('rsID' in ss.data.columns)):
            ss.fix_id(fixid=True)
            log(f"{base}: IDs fixed")

        # Plotting
        qq_plot = os.path.join(plots_dir, f"{base}_qq.png")
        ss.plot_mqq(save=qq_plot, mode="qq", check=False)

        manhattan_plot = os.path.join(plots_dir, f"{base}_manhattan.png")
        ss.plot_mqq(
            save=manhattan_plot,
            mode="m",
            sig_level_lead=5e-8,
            skip=2,
            check=False
        )
        log(f"{base}: Plots generated")

        # Fill missing BETA/SE
        ss.fill_data(to_fill=["BETA", "SE"])
        
        # Extract lead SNPs
        leads = ss.get_lead(anno=True, build=genome_build)
        leads_file = os.path.join(job_dir, f"{base}_leads.tsv")
        leads.to_csv(leads_file, sep='\t', index=False)

        log(f"{base}: Lead SNPs saved → {leads_file}")

        # Save processed data - provide prefix only, to_format adds extension
        processed_file_prefix = os.path.join(job_dir, f"{base}_processed")
        ss.to_format(processed_file_prefix, fmt="ssf")
        processed_file = processed_file_prefix + ".ssf.tsv.gz"

        # Add all output files to result
        result["out_files"].extend([
            leads_file,
            processed_file,
            qq_plot,
            manhattan_plot
        ])

    except Exception as e:
        result["error"] = str(e)
    return result


def main():
    log("Starting GWASLab processing pipeline")

    # Gather all relevant files with supported extensions
    files = glob.glob(os.path.join(job_dir, "*.tsv")) \
          + glob.glob(os.path.join(job_dir, "*.csv")) \
          + glob.glob(os.path.join(job_dir, "*.gz")) \
          + glob.glob(os.path.join(job_dir, "*.txt"))

    results = []    

    # Safe HDD limit
    max_workers = min(len(files), MAX_WORKERS_HDD)
    log(f"Using up to {max_workers} parallel processes (HDD safe limit)")

    with ProcessPoolExecutor(max_workers=max_workers) as executor:
        future_to_file = {executor.submit(process_file, f): f for f in files}
        for future in as_completed(future_to_file):
            result = future.result()
            base = result["file"]
            if result["error"]:
                log(f"ERROR processing {base}: {result['error']}")
            else:
                for of in result["out_files"]:
                    log(f"Generated: {os.path.basename(of)}")
                results.extend(result["out_files"])
                log(f"Processed and plotted {base}")

    # Archive all results safely
    zip_path = os.path.join(job_dir, f"{job_id}_results.zip")
    with zipfile.ZipFile(zip_path, 'w') as zf:
        for fname in results:
            arcname = os.path.basename(fname)
            if os.path.exists(fname):
                zf.write(fname, arcname=arcname)
                log(f"Added to ZIP: {arcname}")
            else:
                log(f"WARNING: File not found, skipping ZIP addition: {arcname}")

    log(f"Archive created at {zip_path}")
    log("GWASLab processing finished")


if __name__ == "__main__":
    main()

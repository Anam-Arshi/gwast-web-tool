#!/usr/bin/env python3
import os
import sys
import json
import gwaslab as gl

def log(msg, logfile):
    with open(logfile, "a") as lf:
        lf.write(f"[harmonize] {msg}\n")
    print(msg, flush=True)

def main():
    if len(sys.argv) < 2:
        print("Usage: harmonize_for_meta.py <job_id>")
        sys.exit(1)

    job_id = sys.argv[1]
    job_dir = f"user_uploads/{job_id}"
    log_file = os.path.join(job_dir, "harmonization.log")
    
    harm_dir = os.path.join(job_dir, "harmonized_files")
    os.makedirs(harm_dir, exist_ok=True)
    os.chmod(harm_dir, 0o777)

    meta_input_path = os.path.join(job_dir, "meta_input.json")
    if not os.path.exists(meta_input_path):
        log(f"meta_input.json not found in {job_dir}", log_file)
        sys.exit(1)

    # Load meta_input.json
    with open(meta_input_path, "r") as f:
        meta_data = json.load(f)

    # Extract target build for harmonization
    target_build = meta_data.get("target_build", "38")

    # Extract files data (assumed structure: files is array of dicts with filename, build, harmonize, liftover)
    files = meta_data.get("files", [])

    log(f"Starting harmonization for job {job_id}, target genome build: hg{target_build}", log_file)

    for file_entry in files:
        filename = file_entry.get("filename")
        build = file_entry.get("build")
        liftover_flag = file_entry.get("liftover", False)
        harmonize_flag = file_entry.get("harmonize", False)
        population = file_entry.get("population", "None")  # Default to EAS if not specified

        if not filename:
            log("Missing filename in input entry, skipping", log_file)
            continue

        # Construct the processed file path
        processed_filename = filename.replace(".tsv", "_processed.ssf.tsv")
        infile = os.path.join(job_dir, processed_filename)
        if not os.path.exists(infile):
            log(f"Input file missing: {infile}, skipping", log_file)
            continue

        working_file = infile

        # Only perform liftover if flagged
        if liftover_flag:
            log(f"Performing liftover for {filename} from hg{build} to hg{target_build}", log_file)
            try:
                mysumstats = gl.Sumstats(infile, fmt="ssf", build=build)
                mysumstats.liftover(n_cores=3, from_build=build, to_build=target_build)
                lifted_file = os.path.join(job_dir, f"{os.path.splitext(filename)[0]}_liftovered_hg{target_build}")
                mysumstats.to_format(lifted_file, fmt="ssf")
                working_file = lifted_file + ".ssf.tsv.gz"
            except Exception as e:
                log(f"ERROR during liftover for {filename}: {e}", log_file)
                continue  # Skip harmonization for this file if liftover fails

        # Only perform harmonization if flagged
        if harmonize_flag:
            outfile = os.path.join(harm_dir, f"{os.path.splitext(filename)[0]}_harmonized")
            try:
                log(f"Harmonizing {filename} using genome build hg{target_build}", log_file)
                ss = gl.Sumstats(working_file, fmt="ssf", build=target_build)
                ss.harmonize(
                    basic_check=False,
                    n_cores=3,
                    ref_seq=gl.get_path(f"ucsc_genome_hg{target_build}"),
                    ref_infer=gl.get_path(f"1kg_{population}_hg{target_build}"),
                    ref_alt_freq="AF"
                )
                ss.fill_data(to_fill=["BETA", "SE"])
                ss.to_format(outfile, fmt="ssf")
                log(f"Harmonized file saved: {outfile}", log_file)
            except Exception as e:
                log(f"ERROR harmonizing {filename}: {e}", log_file)
        else:
            log(f"Skipping harmonization for {filename} as per flag", log_file)

if __name__ == "__main__":
    main()

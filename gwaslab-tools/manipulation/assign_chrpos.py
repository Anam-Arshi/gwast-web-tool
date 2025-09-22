#!/usr/bin/env python3
import os, sys, json
import gwaslab as gl

def main(json_file):
    # Load task JSON
    with open(json_file, "r") as f:
        config = json.load(f)

    task = config.get("task")
    if task != "assign_chrpos":
        print(f"Invalid task: {task}")
        sys.exit(1)

    input_file = config["input_file"]
    output_file = config["output_file"]
    fmt = config.get("format", "auto")
    params = config["params"]

    ref_genome = params.get("ref_genome", "19")  # "19" or "38"
    ref_panel  = params.get("ref_panel", "1kg") # "1kg" or "dbsnp"

    print(f"Assigning CHR/POS using rsID...")
    print(f"Input file: {input_file}")
    print(f"Genome build: {ref_genome}, Panel: {ref_panel}")

    # Load summary stats
    sumstats = gl.Sumstats(input_file, fmt=fmt)

    # ---- Map build + panel to correct path ----
    if ref_panel == "1kg":
        if ref_genome == "19":
            path = gl.get_path("1kg_dbsnp151_hg19_auto")
        elif ref_genome == "38":
            path = gl.get_path("1kg_dbsnp151_hg38_auto")
        else:
            print("Invalid genome build for 1kg panel")
            sys.exit(1)
        print(f"Using 1KG reference path: {path}")
        sumstats.rsid_to_chrpos(path=path)

    elif ref_panel == "dbsnp":
        if ref_genome == "19":
            path = "/home/yunye/work/gwaslab/examples/vcf_hd5/rsID_CHR_POS_groups_hg19.h5"
        elif ref_genome == "38":
            path = "/home/yunye/work/gwaslab/examples/vcf_hd5/rsID_CHR_POS_groups_hg38.h5"
        else:
            print("Invalid genome build for dbSNP panel")
            sys.exit(1)
        print(f"Using dbSNP reference file: {path}")
        sumstats.rsid_to_chrpos2(path=path, n_cores=4)

    else:
        print(f"Unknown reference panel: {ref_panel}")
        sys.exit(1)

    # Save output
    sumstats.data.to_csv(output_file, sep="\t", index=False)
    sumstats.log.save(output_file + ".log")
    sumstats.summary().to_csv(output_file + "_summary.txt", sep="\t", index=False)

    print(f"Output written to {output_file}")

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: assign_chrpos.py task_assign_chrpos.json")
        sys.exit(1)
    main(sys.argv[1])

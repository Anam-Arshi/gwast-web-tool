#!/usr/bin/env python3
import sys
import gwaslab as gl
import pandas as pd
import os
import json

def main():
    if len(sys.argv) < 2:
        print("Usage: standardize.py <config.json>", file=sys.stderr)
        sys.exit(1)

    config_file = sys.argv[1]

    # Load JSON config
    try:
        with open(config_file, "r") as f:
            config = json.load(f)
    except Exception as e:
        print(f"Error loading config file {config_file}: {e}", file=sys.stderr)
        sys.exit(1)

    # Extract parameters from JSON
    input_path = config.get("input_file")
    output_path = config.get("output_file")
    fmt = config.get("format", "auto")

    params = config.get("params", {})

    remove_duplicates = str(params.get("remove_duplicates", "off")).lower() in ["true", "on", "1", "yes"]
    remove_multiallelic = str(params.get("remove_multiallelic", "off")).lower() in ["true", "on", "1", "yes"]

    remove_na = str(params.get("remove_na", "off")).lower() in ["true", "on", "1", "yes"]

    if not input_path or not output_path:
        print("Error: 'input' and 'output' must be specified in JSON config", file=sys.stderr)
        sys.exit(1)

    try:
        print(f"Loading file: {input_path}")

        # Load summary statistics
        if fmt == "auto":
            sumstats = gl.Sumstats(input_path, fmt="auto")
        else:
            sumstats = gl.Sumstats(input_path, fmt=fmt)

        print(f"Original dataset: {len(sumstats.data)} variants")

        # Basic standardization
        print("Performing basic standardization...")
        sumstats.basic_check()

        # Apply filters
        if remove_multiallelic:
            print("Removing multiallelic variants...")
            sumstats.remove_dup(mode="m")
            print(f"After filtering alleles: {len(sumstats.data)} variants")

        if remove_duplicates:
            print("Removing duplicates...")
            sumstats.remove_dup(mode="d")
            print(f"After removing duplicates: {len(sumstats.data)} variants")

        if remove_na:
            print("Removing rows with missing values...")
            sumstats.data = sumstats.data.dropna()
            print(f"After removing NAs: {len(sumstats.data)} variants")

        # Ensure output directory exists
        output_dir = os.path.dirname(output_path)
        if output_dir and not os.path.exists(output_dir):
            os.makedirs(output_dir)

        # Save as TSV
        print(f"Saving to: {output_path}")
        sumstats.data.to_csv(output_path, sep="\t", index=False)
        sumstats.log.save(output_path + ".log")
        sumstats.summary().to_csv(output_path + "_summary.txt", sep="\t", index=False)

        print("Standardization completed successfully!")
        print(f"Final dataset: {len(sumstats.data)} variants")

    except Exception as e:
        print(f"Error: {str(e)}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()

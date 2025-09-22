#!/usr/bin/env python3
import os
import sys
import json
import gwaslab as gl

def run_data_conversion(config_path):
    # Load JSON config
    with open(config_path, "r") as f:
        config = json.load(f)

    input_file = config["input_file"]
    output_file = config["output_file"]
    fmt = config.get("format", "auto")

    params = config.get("params", {})

    # --- Extract parameters safely ---
    to_fill = params.get("to_fill", [])
    df = params.get("df", None)  # can be None
    overwrite = params.get("overwrite", "off") == "on"
    only_sig = params.get("only_sig", "off") == "on"

    # --- Load summary stats ---
    mysumstats = gl.Sumstats(input_file, fmt=fmt)

    # --- Run fill_data with options ---
    mysumstats.fill_data(
        to_fill=to_fill,
        df=df,
        overwrite=overwrite,
        only_sig=only_sig
    )

    # --- Save output ---
    mysumstats.data.to_csv(output_file, sep="\t", index=False)
    mysumstats.log.save(output_file + ".log")
    mysumstats.summary().to_csv(output_file + "_summary.txt", sep="\t", index=False)
    print(f"[OK] Data conversion complete → {output_file}")


if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python data_conversion.py <config.json>")
        sys.exit(1)

    config_path = sys.argv[1]
    if not os.path.exists(config_path):
        print(f"Config file not found: {config_path}")
        sys.exit(1)

    run_data_conversion(config_path)

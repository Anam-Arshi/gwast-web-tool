#!/usr/bin/env python3
import os, sys, json
import gwaslab as gl

def main(json_file):
    # Load task JSON
    with open(json_file, "r") as f:
        config = json.load(f)

    task = config.get("task")
    if task != "liftover":
        print(f"Invalid task: {task}")
        sys.exit(1)

    input_file = config["input_file"]
    output_file = config["output_file"]
    format = config.get("format", "ssf")  # default to ssf
    params = config["params"]

    build = params.get("build")
    target_build = params.get("target_build")
    remove = params.get("remove", "off") in ["on", "true", True]

    if build == target_build:
        print("⚠️ Current build and target build are the same. No liftover needed.")
        sys.exit(0)

    print(f"Lifting over from {build} to {target_build}...")
    print(f"Input file: {input_file}")

    # Load summary stats
    sumstats = gl.Sumstats(input_file, fmt=format, build=build)

    # Run liftover
    # sumstats.basic_check(n_cores=2)
    sumstats.liftover(n_cores=3, from_build=build, to_build=target_build, remove=remove)
    sumstats.harmonize(n_cores=2)

    # Save output
    # sumstats.to_format(output_file, fmt="ssf")
    sumstats.data.to_csv(output_file, sep="\t", index=False)
    sumstats.log.save(output_file + ".log")
    sumstats.summary().to_csv(output_file + "_summary.txt", sep="\t", index=False)
    print(f"✅ Liftover complete. Output written to {output_file}")

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: liftover_utils.py task_liftover.json")
        sys.exit(1)
    main(sys.argv[1])

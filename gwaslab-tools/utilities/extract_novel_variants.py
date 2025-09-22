import sys
import json
import os
import gwaslab as gl
import pandas as pd

def log(msg):
    print("[extract_novel_variants]", msg)

def main():
    if len(sys.argv) < 2:
        log("No config file path provided.")
        sys.exit(1)
    config_path = sys.argv[1]
    log(f"Loading config: {config_path}")
    
    with open(config_path, "r") as f:
        config = json.load(f)
    input_file  = config.get("input_file")
    output_file = config.get("output_file")
    format      = config.get("format")
    params      = config.get("params", {})

    # Parse options
    efo           = params.get("novel_efo", "").strip() or None
    known_file    = params.get("novel_known_file", "").strip() or None
    window_size   = int(params.get("window_size", 500))
    window_size_novel = int(params.get("window_size_novel", 1000))
    sig_level     = float(params.get("sig_level", 5e-8))
    build         = str(params.get("build", "19"))
    only_novel    = bool(params.get("only_novel", False))
    output_known  = bool(params.get("output_known", False))
    verbose       = True

    log(f"Input file: {input_file}")
    log(f"Output file: {output_file}")
    log(f"Options: efo={efo}, known_file={known_file}, window_size={window_size}, window_size_novel={window_size_novel}, sig_level={sig_level}, build={build}, only_novel={only_novel}, output_known={output_known}")

    try:
        # Load summary statistics
        if not os.path.isfile(input_file):
            raise FileNotFoundError(f"Input file not found: {input_file}")
        sumstats = gl.Sumstats(input_file, fmt=format,  build=build)
        sumstats.basic_check()

        # Check for known loci file (handle uploaded file path if present)
        known_path = None
        if known_file and os.path.isfile(known_file):
            known_path = known_file
            log(f"Using user-provided known loci file: {known_path}")

        # Extract novel loci
        df_novel = sumstats.get_novel(
            known=known_path,
            efo=efo,
            only_novel=only_novel,
            windowsizekb_for_novel=window_size_novel,
            windowsizekb=window_size,
            sig_level=sig_level,
            output_known=output_known,
            verbose=verbose
        )

        # Save result
        df_novel.to_csv(output_file, sep="\t", index=False)
        log(f"Novel variant extraction complete. Output: {output_file} (rows: {len(df_novel)})")

    except Exception as e:
        log(f"ERROR: {e}")
        sys.exit(2)

if __name__ == "__main__":
    main()

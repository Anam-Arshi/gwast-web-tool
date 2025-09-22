import sys
import json
import os
import gwaslab as gl
import pandas as pd

def log(msg):
    print("[extract_lead_variants]", msg)

def main():
    if len(sys.argv) < 2:
        log("No config file path provided.")
        sys.exit(1)
    config_path = sys.argv[1]
    log(f"Loading config: {config_path}")
    
    with open(config_path, "r") as f:
        config = json.load(f)

    # Required arguments
    input_file  = config.get("input_file")
    output_file = config.get("output_file")
    format      = config.get("format")
    params      = config.get("params", {})
    
    # Parse options from user (with defaults)
    method         = params.get("lead_method", "mlog10p")
    window_size    = int(params.get("window_size", 500))
    sig_level      = float(params.get("sig_level", 5e-8))
    build          = str(params.get("build", "19"))
    annotate_lead  = bool(params.get("annotate_lead", False))
    verbose        = bool(params.get("verbose_logs", True))
    
    source         = "ensembl"  # for annotation
    # Derived logic for GWASLab .get_lead()
    # - If method is "mlog10p": use_p = False
    # - If method is "pvalue": use_p = True
    
    use_p = (method == "pvalue")
    
    log(f"Input file: {input_file}")
    log(f"Output file: {output_file}")
    log(f"Options: window_size={window_size}, sig_level={sig_level}, build={build}, annotate_lead={annotate_lead}, use_p={use_p}, verbose={verbose}")
    
    try:
        # Load input sumstats file
        if not os.path.isfile(input_file):
            raise FileNotFoundError(f"Input file not found: {input_file}")
        
        sumstats = gl.Sumstats(input_file, fmt=format)
        # Optional: run basic_check for quality, if desired
        sumstats.basic_check(n_cores=3, build=build)
        
        # Run lead extraction
        df_lead = sumstats.get_lead(
            use_p=use_p,
            windowsizekb=window_size,
            sig_level=sig_level,
            anno=annotate_lead,
            build=build,
            source=source,
            verbose=verbose,
            gls=False  # always return pandas DataFrame
        )
        
        # Save output TSV
        df_lead.to_csv(output_file, sep="\t", index=False)
        log(f"Lead variants extraction complete. Output: {output_file} (variants: {len(df_lead)})")
        
    except Exception as e:
        log(f"ERROR: {e}")
        sys.exit(2)

if __name__ == "__main__":
    main()

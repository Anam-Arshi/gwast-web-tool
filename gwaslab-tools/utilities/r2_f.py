import sys
import json
import os
import gwaslab as gl

def log(msg):
    print("[r2_f]", msg)

def main():
    if len(sys.argv) < 2:
        log("No config file path provided.")
        sys.exit(1)
    config_path = sys.argv[1]
    with open(config_path, "r") as f:
        config = json.load(f)

    input_file = config.get("input_file")
    output_file = config.get("output_file")
    format_in = config.get("format")
    params = config.get("params", {})

    mode = params.get("mode", "q")  # 'q' or 'b'
    vary = params.get("vary", "1")  # '1' or 'se'
    k = int(params.get("k", 1))

    # For binary traits
    ncase = params.get("ncase")
    ncontrol = params.get("ncontrol")
    prevalence = params.get("prevalence")

    log(f"Input: {input_file}, mode: {mode}, vary: {vary}, k: {k}")

    try:
        if not os.path.exists(input_file):
            raise FileNotFoundError(f"Input file not found: {input_file}")

        sumstats = gl.Sumstats(input_file, fmt=format_in)
        sumstats.basic_check()

        # Convert ncase, ncontrol, prevalence to correct types if available
        if mode == "b":
            if ncase is None or ncontrol is None or prevalence is None:
                raise ValueError("For binary traits, ncase, ncontrol, and prevalence are required.")
            ncase = int(ncase)
            ncontrol = int(ncontrol)
            prevalence = float(prevalence)

        # Calculate per-SNP R2 and F
        df_out = sumstats.get_per_snp_r2(
            mode=mode,
            vary=vary,
            k=k,
            ncase=ncase,
            ncontrol=ncontrol,
            prevalence=prevalence
        )

        # Save output TSV
        df_out.to_csv(output_file, sep="\t", index=False)
        log(f"Per-SNP R2 and F calculation completed. Output saved to {output_file} with {len(df_out)} rows.")

    except Exception as e:
        log(f"ERROR: {e}")
        sys.exit(2)

if __name__ == "__main__":
    main()

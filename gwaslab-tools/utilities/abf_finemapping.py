import sys
import json
import os
import gwaslab as gl

def log(msg):
    print("[abf_finemapping]", msg)

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

    # Parse parameters
    locus_chr = params.get("locus_chr")
    locus_start = params.get("locus_start")
    locus_end = params.get("locus_end")
    prior_var = float(params.get("prior_var", 0.04))
    sig_level = float(params.get("sig_level", 5e-8))
    credible_set_threshold = float(params.get("credible_set", 0.95))
    use_maf_prior = bool(params.get("use_maf_prior", False))
    output_pip = bool(params.get("output_pip", True))

    log(f"Input: {input_file}")
    log(f"Prior variance: {prior_var}, Sig level: {sig_level}")

    try:
        if not os.path.exists(input_file):
            raise FileNotFoundError(f"Input file not found: {input_file}")

        # Load summary statistics
        sumstats = gl.Sumstats(input_file, fmt=format_in)
        sumstats.basic_check()
        
        # Build region tuple if chromosome and positions are specified
        region = None
        if locus_chr is not None:
            if locus_start is not None and locus_end is not None:
                region = (int(locus_chr), int(locus_start), int(locus_end))
            else:
                # If only chromosome specified, use whole chromosome
                region = (int(locus_chr),)

        log(f"Fine-mapping region: {region}")

        # Run ABF fine-mapping using GWASLab's built-in method
        result = sumstats.abf_finemapping(
            region=region,
            prior_var=prior_var,
            sig_level=sig_level,
            credible_set=credible_set_threshold,
            use_maf_prior=use_maf_prior,
            output_pip=output_pip
        )
        
        # Save results to TSV
        result.to_csv(output_file, sep='\t', index=False)
        
        log(f"ABF fine-mapping completed. Output saved to {output_file}")
        log(f"Number of variants analyzed: {len(result)}")
        
        if 'ABF' in result.columns:
            log(f"Top variant ABF: {result['ABF'].max():.3f}")
        if 'PIP' in result.columns:
            log(f"Top variant PIP: {result['PIP'].max():.3f}")
            credible_variants = result[result.get('IN_CREDIBLE_SET', False) == True]
            log(f"Credible set contains {len(credible_variants)} variants")

    except Exception as e:
        log(f"ERROR: {e}")
        sys.exit(2)

if __name__ == "__main__":
    main()

import sys
import json
import os
import gwaslab as gl

def log(msg):
    print("[format_save]", msg)

def parse_list(value):
    # Accept comma-separated or list; return None if empty
    if not value:
        return None
    if isinstance(value, list):
        return value
    return [x.strip() for x in value.split(',') if x.strip()]

def to_bool(val):
    # Converts "on", "1", 1, True, etc. to True, else False
    if isinstance(val, bool): return val
    if isinstance(val, int): return bool(val)
    return str(val).lower() in ("1", "true", "on", "yes")

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
    format_in   = config.get("format")
    params      = config.get("params", {})

    log(f"Input: {input_file}\nOutput: {output_file}")

    # Parse user options from the form
    fmt = params.get("export_format", "gwaslab")
    cols = parse_list(params.get("export_cols", None))
    extract = parse_list(params.get("extract_snps", None))
    exclude = parse_list(params.get("exclude_snps", None))
    id_use = params.get("id_use", "rsID")
    hapmap3 = to_bool(params.get("hapmap3", False))
    exclude_hla = to_bool(params.get("exclude_hla", False))
    build = str(params.get("build", "19"))
    bgzip = to_bool(params.get("bgzip", False))
    tabix = to_bool(params.get("tabix", False))
    md5sum = to_bool(params.get("md5sum", False))

    # Automatic: output path is prefix (without extension)
    prefix = os.path.splitext(output_file)

    log(f"to_format options: fmt={fmt}, prefix={prefix}, cols={cols}, extract={extract}, exclude={exclude}, id_use={id_use}, hapmap3={hapmap3}, exclude_hla={exclude_hla}, build={build}, bgzip={bgzip}, tabix={tabix}, md5sum={md5sum}")

    try:
        # Load sumstats (infer input format if possible)
        sumstats = gl.Sumstats(input_file, fmt=format_in)
        sumstats.basic_check(n_cores=3)

        # Call GWASLab .to_format
        sumstats.to_format(
            path=prefix,
            fmt=fmt,
            cols=cols,
            extract=extract,
            exclude=exclude,
            id_use=id_use,
            hapmap3=hapmap3,
            exclude_hla=exclude_hla,
            build=build,
            bgzip=bgzip,
            tabix=tabix,
            md5sum=md5sum,
            verbose=True,        # always verbose for logs
            output_log=True      # always output logs
        )

        # Find produced file(s) for result
        # For most cases, main output is at <prefix>.<fmt or tsv> (plus possible bgzip/md5/log)
        possible_files = [
            f"{prefix}.{fmt}",                      # e.g. sumstats.ldsc, sumstats.plink, etc.
            f"{prefix}.tsv",                        # fallback
            f"{prefix}.txt",
            f"{prefix}.{fmt}.gz",                   # if bgzip
            f"{prefix}.{fmt}.bgz",
            f"{prefix}.{fmt}.md5",
            f"{prefix}.{fmt}.log",                  # log
            f"{prefix}.log"
        ]
        out_found = None
        for p in possible_files:
            if os.path.exists(p):
                out_found = p
                break
        if not out_found:
            log(f"WARNING: Output file not found; tried {possible_files}")

        log("Formatting/export complete.")
    except Exception as e:
        log(f"ERROR: {e}")
        sys.exit(2)

if __name__ == "__main__":
    main()

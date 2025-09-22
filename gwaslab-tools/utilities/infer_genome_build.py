import sys
import json
import os
import gwaslab as gl

def log(msg):
    print("[infer_genome_build]", msg)

def main():
    if len(sys.argv) < 2:
        log("No config file path provided.")
        sys.exit(1)

    config_path = sys.argv[1]
    with open(config_path, "r") as f:
        config = json.load(f)

    input_file = config.get("input_file")
    format_in = config.get("format")

    try:
        if not os.path.exists(input_file):
            raise FileNotFoundError(f"Input file not found: {input_file}")

        sumstats = gl.Sumstats(input_file, fmt=format_in)
        sumstats.basic_check(n_cores=3)

        # Infer genome build
        result = sumstats.infer_build()

        # result is typically a dict, for example: {'build': '19' or '38', 'status_code': xx, ...}

        print(json.dumps({
            "success": True,
            "result": result
        }, indent=2))

    except Exception as e:
        log(f"ERROR: {e}")
        print(json.dumps({
            "success": False,
            "error": str(e)
        }))
        sys.exit(2)

if __name__ == "__main__":
    main()

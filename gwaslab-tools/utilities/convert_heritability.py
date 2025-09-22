import sys
import json
import gwaslab as gl

def log(msg):
    print("[convert_heritability]", msg)

def main():
    if len(sys.argv) < 2:
        log("No config file path provided.")
        sys.exit(1)
    config_path = sys.argv[1]
    with open(config_path, "r") as f:
        config = json.load(f)
    params = config.get("params", {})

    try:
        h2_obs = float(params.get("h2_obs"))
        P = float(params.get("P"))
        K = float(params.get("K"))
        se_obs = params.get("se_obs")
        se_obs = float(se_obs) if se_obs is not None else None

        # Validate inputs
        if not (0 <= h2_obs <= 1 and 0 < P < 1 and 0 < K < 1):
            raise ValueError("h2_obs must be [0,1], P and K must be (0,1)")

        h2_liab, se_liab = gl.h2_obs_to_liab(h2_obs, P, K, se_obs)

        result = {
            "h2_liab": h2_liab,
            "se_liab": se_liab
        }

        print(json.dumps({"success": True, "result": result}, indent=2))

    except Exception as e:
        log(f"ERROR: {e}")
        print(json.dumps({"success": False, "error": str(e)}))
        sys.exit(2)

if __name__ == "__main__":
    main()

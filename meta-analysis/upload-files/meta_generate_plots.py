import sys
import os
import json
import gwaslab as gl

if len(sys.argv) < 2:
    print("Usage: python3 meta_generate_plots.py <job_id>")
    sys.exit(1)

job_id = sys.argv[1]
folder = f"../../user_uploads/{job_id}"
plot_dir = os.path.join(folder, "meta_plots")
os.makedirs(plot_dir, exist_ok=True)

meta_file = os.path.join(folder, "meta_results.tsv")
meta_info_file = os.path.join(folder, "meta_info.json")

if not os.path.exists(meta_file):
    print("meta_results.tsv not found")
    sys.exit(1)

# Read build from meta_info.json
build = "38"  # Default
if os.path.exists(meta_info_file):
    try:
        with open(meta_info_file, "r") as f:
            meta_info = json.load(f)
            build = meta_info.get("target_build", "38")
    except Exception as e:
        print(f"Failed to read meta_info.json: {e}")

try:
    print(f"Reading {meta_file} with build: {build}")
    ss =  gl.Sumstats(meta_file, 
                  rsid="rsid",
                  chrom="chromosome",
                  pos="base_pair_location",
                #   ea="effect_allele",
                #   nea="other_allele",
                #   OR="OR_fixed",
                  p="p_value_fixed",
                  build=build)

    base = job_id  # base name for plots
    qq_plot = os.path.join(plot_dir, "meta_qq.png")
    ss.plot_mqq(save=qq_plot, mode="qq", check=False, verbose=False)

    manhattan_plot = os.path.join(plot_dir, "meta_manhattan.png")
    ss.plot_mqq(
        save=manhattan_plot,
        mode="m",
        anno="GENENAME",
        sig_level_lead=5e-8,
        anno_style="expand",
        check=False,
        verbose=False
    )

    print("Plots generated")
except Exception as e:
    print(f"Error: {str(e)}")
    sys.exit(1)

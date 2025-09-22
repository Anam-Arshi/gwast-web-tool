import sys
import os
import json
import gwaslab as gl

if len(sys.argv) < 2:
    print("Usage: python3 meta_extract_leads.py <job_id>")
    sys.exit(1)

job_id = sys.argv[1]
window_kb =  int(sys.argv[2]) 
pval_threshold = float(sys.argv[3])

folder = f"user_uploads/{job_id}"
input_file = os.path.join(folder, "meta_results.tsv")
output_file = os.path.join(folder, "meta_leads.tsv")

if not os.path.exists(input_file):
    print("meta_results.tsv not found")
    sys.exit(1)


# Set default build; will be overwritten if meta_info.json exists
build = "38"  # Default
meta_info_file = os.path.join(folder, "meta_info.json")
if os.path.exists(meta_info_file):
    try:
        with open(meta_info_file, "r") as f:
            meta_info = json.load(f)
            build = meta_info.get("target_build", "38")
    except Exception as e:
        print(f"Failed to read meta_info.json: {e}")

try:
    print(f"Reading {input_file} with build: {build}")
    ss =  gl.Sumstats(input_file, 
                  rsid="rsid",
                  chrom="chromosome",
                  pos="base_pair_location",
                  ea="effect_allele",
                #   nea="other_allele",
                  OR="OR_fixed",
                  p="p_value_fixed",
                  build=build)

    print("Extracting lead SNPs")
    leads = ss.get_lead(anno=True, build=build, sig_level=pval_threshold, windowsizekb=window_kb)
    leads.to_csv(output_file, sep='\t', index=False)
    print(f"Lead SNPs extracted to {output_file}")
     
    # finemap = ss.abf_finemapping()
    # finemap.to_csv(os.path.join(folder, "finemap.tsv"), sep='\t', index=False)
    
   
       
except Exception as e:
    print(f"Error: {str(e)}")
    sys.exit(1)

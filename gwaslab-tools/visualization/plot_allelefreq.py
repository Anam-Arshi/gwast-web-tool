import sys
import json
import os
import gwaslab

def parse_bool(val):
    if isinstance(val, bool):
        return val
    if not val:
        return False
    return str(val).strip().lower() in ['true', '1', 'yes']

def main(config_json):
    with open(config_json, 'r') as f:
        config = json.load(f)

    input_file = config['input_file']
    output_file = config['output_file']
    format = config.get('format', 'auto')
    params = config.get('params', {})

    mysumstats = gwaslab.Sumstats(input_file, fmt=format)

    # Construct VCF panel key
    population = params.get('ref_population')
    build = params.get('ref_build')
    if not population or not build:
        raise ValueError("Reference population and build must be specified")

    vcf_panel_key = f"1kg_{population}_hg{build}"
    vcf_path = gwaslab.get_path(vcf_panel_key)
    if not vcf_path:
        raise ValueError(f"VCF panel path not found for {vcf_panel_key}")

    # Run check_af to harmonize allele frequencies
    # Uses ref_alt_freq="AF" by default as in GWASLab docs
    print(f"Checking allele frequencies using reference panel: {vcf_panel_key}")
    mysumstats.check_af(ref_infer=vcf_path, ref_alt_freq="AF", n_cores=3)

    # Parse plotting parameters
    try:
        threshold = float(params.get('threshold', 0.12))
    except:
        threshold = 0.12

    try:
        dpi = int(params.get('dpi', 300))
        if dpi <= 0:
            dpi = 300
    except:
        dpi = 300

    save_file = params.get('save', None)
    if save_file:
        if not save_file.lower().endswith('.png'):
            save_file += '.png'

    plot_kwargs = {
        'threshold': threshold,
        'save': save_file or None,
        'save_args': {'dpi': dpi, 'facecolor': 'white'},
    }

    # Remove None keys to apply defaults in GWASLab
    plot_kwargs = {k: v for k, v in plot_kwargs.items() if v is not None}

    # Generate the allele frequency comparison plot
    mysumstats.plot_daf(**plot_kwargs)

    print(f"Allele frequency plot saved to {save_file or 'not saved'}")

if __name__ == '__main__':
    if len(sys.argv) != 2:
        print("Usage: python plot_allelefreq.py <config.json>")
        sys.exit(1)
    main(sys.argv[1])

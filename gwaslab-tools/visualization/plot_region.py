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

def parse_region(locus_center, window_kb, region_text):
    if region_text:
        try:
            chrom, rest = region_text.strip().split(":")
            start, end = rest.split("-")
            return (int(chrom) if chrom.isdigit() else chrom, int(start), int(end))
        except Exception as e:
            raise ValueError(f"Invalid region_text format: {region_text}") from e
    elif locus_center and window_kb:
        try:
            chrom, pos = locus_center.strip().split(":")
            pos = int(pos)
            win = int(window_kb) * 1000
            start = max(0, pos - win)
            end = pos + win
            return (int(chrom) if chrom.isdigit() else chrom, start, end)
        except Exception as e:
            raise ValueError(f"Invalid locus_center or window_kb values: {locus_center}, {window_kb}") from e
    else:
        return None

def main(config_json):
    with open(config_json) as f:
        config = json.load(f)

    input_file = config['input_file']
    output_file = config['output_file']
    format = config.get('format', 'auto')
    params = config.get('params', {})
    
    if format == 'auto':
        mysumstats = gwaslab.Sumstats(input_file, fmt=format, nea="other_allele")
    else:
        mysumstats = gwaslab.Sumstats(input_file, fmt=format)

    locus_center = params.get('locus_center', '')
    window_kb = params.get('window_kb', '')
    region_text = params.get('region_text', '')

    region = parse_region(locus_center, window_kb, region_text)

    vcf_panel_map = {
        "1kg_eas_hg19": gwaslab.get_path("1kg_eas_hg19"),
        "1kg_eur_hg19": gwaslab.get_path("1kg_eur_hg19"),
        "1kg_eas_hg38": gwaslab.get_path("1kg_eas_hg38"),
        "1kg_eur_hg38": gwaslab.get_path("1kg_eur_hg38"),
    }
    vcf_panel = params.get('vcf_panel')
    vcf_path = vcf_panel_map.get(vcf_panel) if vcf_panel in vcf_panel_map else None

    region_ref = params.get('region_ref')
    if region_ref:
        region_ref = [x.strip() for x in region_ref.split(",") if x.strip()]
    else:
        region_ref = None

    region_ld_threshold = params.get('region_ld_threshold')
    if region_ld_threshold:
        region_ld_threshold = [float(x.strip()) for x in region_ld_threshold.split(",") if x.strip()]
    else:
        region_ld_threshold = None

    region_ld_colors = params.get('region_ld_colors')
    if region_ld_colors:
        region_ld_colors = [x.strip() for x in region_ld_colors.split(",") if x.strip()]
    else:
        region_ld_colors = None

    region_grid = parse_bool(params.get('region_grid', False))

    val = params.get('region_recombination', '').lower()
    if val == 'true':
        region_recombination = True
    elif val == 'false':
        region_recombination = False
    else:
        region_recombination = None

    try:
        region_step = int(params.get('region_step', 21))
    except:
        region_step = 21


    try:
        region_hspace = float(params.get('region_hspace', 0.02))
    except:
        region_hspace = 0.02

    anno_val = params.get('anno', 'False')
    # Convert 'None' string or empty string to None
    if anno_val.strip().lower() in ['false', 'none', '']:
        anno = False
    elif anno_val.strip().lower() == 'true':
        anno = True
    else:
        anno = anno_val

    anno_set = params.get('anno_set')
    if anno_set:
        anno_set = [x.strip() for x in anno_set.split(",") if x.strip()]
    else:
        anno_set = None

    anno_style = params.get('anno_style') or None
    build = params.get('build') or None

    title = params.get('title') or None

    try:
        fontsize = int(params.get('fontsize', 10))
    except:
        fontsize = 10

    try:
        dpi = int(params.get('dpi', 300))
        if dpi <= 0:
            dpi = 300
    except:
        dpi = 300

    save_pdf = parse_bool(params.get('save_pdf', False))

    plot_kwargs = {
        'mode': 'r',
        'region': region,
        'vcf_path': vcf_path,
        'region_ref': region_ref,
        'region_ld_threshold': region_ld_threshold,
        'region_ld_colors': region_ld_colors,
        'region_grid': region_grid,
        'region_recombination': region_recombination,
        'region_step': region_step,
        'region_hspace': region_hspace,
        'anno': anno,
        'anno_set': anno_set,
        'anno_style': anno_style,
        'build': build,
        'title': title,
        'fontsize': fontsize,
        'figargs': {'figsize': (15, 10), 'dpi': dpi},
        'save_args': {'dpi': dpi, 'facecolor': 'white'},
        'save': output_file,
    }

    # Remove None values to allow defaults in GWASLab
    plot_kwargs = {k: v for k, v in plot_kwargs.items() if v is not None}
    
    print(plot_kwargs) # For debugging

    mysumstats.plot_mqq(**plot_kwargs)

    print(f"Regional plot saved to {output_file}")

    if save_pdf:
        pdf_file = os.path.splitext(output_file)[0] + '.pdf'
        plot_kwargs['save'] = pdf_file
        mysumstats.plot_mqq(**plot_kwargs)
        print(f"Also saved PDF to {pdf_file}")

if __name__ == '__main__':
    if len(sys.argv) != 2:
        print("Usage: python plot_region.py <config.json>")
        sys.exit(1)
    main(sys.argv[1])

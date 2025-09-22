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

    mode = params.get('mode', 'mqq')
    skip = None
    try:
        skip = float(params.get('skip')) if params.get('skip') else None
    except:
        skip = None

    cut = None
    try:
        cut = float(params.get('cut')) if params.get('cut') else None
    except:
        cut = None

    use_rank = parse_bool(params.get('use_rank', False))
    stratified = parse_bool(params.get('stratified', False))

    anno_set = [x.strip() for x in params.get('anno_set', '').split(',') if x.strip()] or None
    highlight = [x.strip() for x in params.get('highlight', '').split(',') if x.strip()] or None
    pinpoint = [x.strip() for x in params.get('pinpoint', '').split(',') if x.strip()] or None

    build = params.get('build', '19') or '19'

    sig_level = 5e-8
    try:
        sig_level = float(params.get('sig_level')) if params.get('sig_level') else 5e-8
    except:
        sig_level = 5e-8

    suggestive_sig_level = 1e-6
    try:
        suggestive_sig_level = float(params.get('suggestive_sig_level')) if params.get('suggestive_sig_level') else 1e-6
    except:
        suggestive_sig_level = 1e-6

    colors = [col.strip() for col in params.get('colors','').split(',') if col.strip()] or None
    highlight_color = params.get('highlight_color') or None
    pinpoint_color = params.get('pinpoint_color') or None

    fontsize = 10
    try:
        fontsize = int(params.get('fontsize', 10))
    except:
        fontsize = 10

    title = params.get('title') or None

    try:
        dpi = int(params.get('dpi', 300))
        if dpi <= 0:
            dpi = 300
    except:
        dpi = 300

    save_pdf = parse_bool(params.get('save_pdf', False))

    plot_kwargs = {
        'mode': mode,
        'skip': skip,
        'cut': cut,
        'use_rank': use_rank,
        'stratified': stratified,
        'anno_set': anno_set,
        'highlight': highlight,
        'pinpoint': pinpoint,
        'build': build,
        'sig_level': sig_level,
        'suggestive_sig_line': True,
        'suggestive_sig_level': suggestive_sig_level,
        'colors': colors,
        'highlight_color': highlight_color,
        'pinpoint_color': pinpoint_color,
        'fontsize': fontsize,
        'title': title,
        'figargs': {'figsize': (15, 5), 'dpi': dpi},
        'save': output_file,
        'save_args': {'dpi': dpi, 'facecolor': "white"},
    }

    # Purge None keys so GWASLab defaults apply
    plot_kwargs = {k: v for k, v in plot_kwargs.items() if v is not None}

    mysumstats.plot_mqq(**plot_kwargs)

    print(f"Plot saved to {output_file}")

    if save_pdf:
        pdf_file = os.path.splitext(output_file)[0] + '.pdf'
        pdf_kwargs = plot_kwargs.copy()
        pdf_kwargs['save'] = pdf_file
        mysumstats.plot_mqq(**pdf_kwargs)
        print(f"Also saved PDF to {pdf_file}")

if __name__ == '__main__':
    if len(sys.argv) != 2:
        print("Usage: python plot_mqq.py <config.json>")
        sys.exit(1)
    main(sys.argv[1])

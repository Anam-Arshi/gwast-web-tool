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

    mode = params.get('mode', 'q')

    # Trait-specific parameters
    if mode == 'q':
        try:
            sig_level = float(params.get('sig_level', 5e-8))
        except:
            sig_level = 5e-8
        try:
            n = int(params.get('n'))
        except:
            n = None
        ts_raw = params.get('ts_q', '')
        ts = [float(x.strip()) for x in ts_raw.split(',') if x.strip()] if ts_raw else [0.2,0.4,0.6,0.8]

        # Binary parameters set to None
        ncase = None
        ncontrol = None
        prevalence = None
        or_to_rr = False

    elif mode == 'b':
        try:
            sig_level = float(params.get('sig_level', 5e-8))
        except:
            sig_level = 5e-8
        try:
            ncase = int(params.get('ncase'))
        except:
            ncase = None
        try:
            ncontrol = int(params.get('ncontrol'))
        except:
            ncontrol = None
        try:
            prevalence = float(params.get('prevalence'))
        except:
            prevalence = None
        or_to_rr = parse_bool(params.get('or_to_rr', False))

        # Quantitative parameters set to None
        n = None
        ts_raw = params.get('ts_b', '')
        ts = [float(x.strip()) for x in ts_raw.split(',') if x.strip()] if ts_raw else [0.2,0.4,0.6,0.8]

    else:
        # Default fallback
        sig_level = 5e-8
        n = None
        ncase = None
        ncontrol = None
        prevalence = None
        or_to_rr = False
        ts = [0.2,0.4,0.6,0.8]

    anno = params.get('anno', None) or None
    anno_style = params.get('anno_style', 'expand')
    build = params.get('build', '19') or '19'
    title = params.get('title', None)
    fontsize = int(params.get('fontsize', 12)) if params.get('fontsize') else 12
    xscale = params.get('xscale', 'log')
    cmap = params.get('cmap', 'cool')
    try:
        dpi = int(params.get('dpi', 300))
        if dpi <= 0:
            dpi = 300
    except:
        dpi = 300

    save_pdf = parse_bool(params.get('save_pdf', False))

    plot_kwargs = {
        'mode': mode,
        'sig_level': sig_level,
        'anno': anno,
        'anno_style': anno_style,
        'build': build,
        'title': title,
        'fontsize': fontsize,
        'xscale': xscale,
        'cmap': cmap,
        'figargs': {'figsize': (15, 8), 'dpi': dpi},
        'save': output_file,
        'save_args': {'dpi': dpi, 'facecolor': 'white'},
        'ts': ts,
    }

    if mode == 'q':
        plot_kwargs['n'] = n
    elif mode == 'b':
        plot_kwargs['ncase'] = ncase
        plot_kwargs['ncontrol'] = ncontrol
        plot_kwargs['prevalence'] = prevalence
        plot_kwargs['or_to_rr'] = or_to_rr

    # Remove None values to allow GWASLab defaults
    plot_kwargs = {k: v for k, v in plot_kwargs.items() if v is not None}

    mysumstats.plot_trumpet(**plot_kwargs)

    print(f"Trumpet plot saved to {output_file}")

    if save_pdf:
        pdf_file = os.path.splitext(output_file)[0] + '.pdf'
        pdf_kwargs = plot_kwargs.copy()
        pdf_kwargs['save'] = pdf_file
        mysumstats.plot_trumpet(**pdf_kwargs)
        print(f"Also saved PDF to {pdf_file}")


if __name__ == '__main__':
    if len(sys.argv) != 2:
        print("Usage: python plot_trumpet.py <config.json>")
        sys.exit(1)
    main(sys.argv[1])

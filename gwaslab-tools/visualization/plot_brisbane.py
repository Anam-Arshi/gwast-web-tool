import sys
import json
import os
import gwaslab

def parse_bool(val):
    if isinstance(val, bool):
        return val
    if not val:
        return False
    return str(val).strip().lower() in ['true','1','yes']

def main(config_json):
    with open(config_json, 'r') as f:
        config = json.load(f)

    input_file = config['input_file']
    output_file = config['output_file']
    format = config.get('format','auto')
    params = config.get('params',{})

    mysumstats = gwaslab.Sumstats(input_file, fmt=format)

    # Brisbane plot parameters
    bwindowsizekb = 100
    try:
        bwindowsizekb = int(params.get('bwindowsizekb', 100))
    except:
        bwindowsizekb = 100

    anno_val = params.get('anno', 'None')
    if anno_val.strip().lower() in ['none', '', 'false']:
        anno = False
    elif anno_val.strip().lower() == 'true':
        anno = True
    else:
        anno = anno_val

    build = params.get('build', '19') or '19'
    title = params.get('title', None)

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
        'mode': 'b',  # Brisbane plot mode
        'bwindowsizekb': bwindowsizekb,
        'anno': anno,
        'build': build,
        'sig_line_color':"red",
        'title': title,
        'fontsize': fontsize,
        'figargs': {'figsize': (15, 5), 'dpi': dpi},
        'save': output_file,
        'save_args': {'dpi': dpi, 'facecolor': 'white'},
    }

    # Clean None values for good practice
    plot_kwargs = {k:v for k,v in plot_kwargs.items() if v is not None}

    mysumstats.plot_mqq(**plot_kwargs)

    print(f"Brisbane plot saved to {output_file}")

    if save_pdf:
        pdf_file = os.path.splitext(output_file)[0] + '.pdf'
        pdf_kwargs = plot_kwargs.copy()
        pdf_kwargs['save'] = pdf_file
        mysumstats.plot_mqq(**pdf_kwargs)
        print(f"Also saved PDF to {pdf_file}")

if __name__ == '__main__':
    if len(sys.argv)!=2:
        print("Usage: python plot_brisbane.py <config.json>")
        sys.exit(1)
    main(sys.argv[1])

import sys
import json
import os
import gwaslab
import pandas as pd

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

    # Load correlation data directly as DataFrame
    ldsc = pd.read_csv(input_file, sep='\t')

    # Column mappings
    p1_col = params.get('p1', 'p1')
    p2_col = params.get('p2', 'p2')
    rg_col = params.get('rg', 'rg')
    p_col = params.get('p', 'p')

    # Significance levels
    sig_levels_str = params.get('sig_levels', '0.05')
    sig_levels = [float(x.strip()) for x in sig_levels_str.split(',') if x.strip()]

    # Multiple testing corrections
    corrections_str = params.get('corrections', 'non')
    if isinstance(corrections_str, list):
        corrections = corrections_str
    else:
        corrections = [x.strip() for x in corrections_str.split(',') if x.strip()]

    # Annotation texts
    panno_texts_str = params.get('panno_texts', '*,**,***')
    panno_texts = [x.strip() for x in panno_texts_str.split(',') if x.strip()]

    # Full cell settings
    full_cell_method = params.get('full_cell_method', 'non')
    try:
        full_cell_threshold = float(params.get('full_cell_threshold', 0.05))
    except:
        full_cell_threshold = 0.05

    full_cell = (full_cell_method, full_cell_threshold)

    # Visual settings
    cmap = params.get('cmap', 'RdBu')
    try:
        fontsize = int(params.get('fontsize', 10))
    except:
        fontsize = 10

    panno = parse_bool(params.get('panno', True))
    equal_aspect = parse_bool(params.get('equal_aspect', True))

    try:
        dpi = int(params.get('dpi', 300))
        if dpi <= 0:
            dpi = 300
    except:
        dpi = 300

    # Figure size
    figsize_str = params.get('figsize', '15,15')
    try:
        figsize = tuple(float(x.strip()) for x in figsize_str.split(','))
        if len(figsize) != 2:
            figsize = (15, 15)
    except:
        figsize = (15, 15)

    save_pdf = parse_bool(params.get('save_pdf', False))

    # Plot arguments
    plot_kwargs = {
        'ldscrg': ldsc,
        'p1': p1_col,
        'p2': p2_col,
        'rg': rg_col,
        'p': p_col,
        'sig_levels': sig_levels,
        'corrections': corrections,
        'panno_texts': panno_texts,
        'full_cell': full_cell,
        'cmap': cmap,
        'fontsize': fontsize,
        'panno': panno,
        'equal_aspect': equal_aspect,
        'fig_args': {'figsize': figsize, 'dpi': dpi},
        'save': output_file,
        'save_args': {'dpi': dpi, 'facecolor': 'white'},
        'panno_args': {'size': fontsize + 2, 'c': 'black'},
        'colorbar_args': {'shrink': 0.82},
        'xticklabel_args': {
            'rotation': 45, 
            'horizontalalignment': 'left', 
            'verticalalignment': 'bottom', 
            'fontsize': fontsize
        },
        'yticklabel_args': {'fontsize': fontsize}
    }

    # Remove None values
    plot_kwargs = {k: v for k, v in plot_kwargs.items() if v is not None}

    # Generate correlation heatmap
    result_df = gwaslab.plot_rg(**plot_kwargs)

    print(f"Correlation heatmap saved to {output_file}")

    if save_pdf:
        pdf_file = os.path.splitext(output_file)[0] + '.pdf'
        pdf_kwargs = plot_kwargs.copy()
        pdf_kwargs['save'] = pdf_file
        gwaslab.plot_rg(**pdf_kwargs)
        print(f"Also saved PDF to {pdf_file}")

if __name__ == '__main__':
    if len(sys.argv) != 2:
        print("Usage: python plot_correlation.py <config.json>")
        sys.exit(1)
    main(sys.argv[1])

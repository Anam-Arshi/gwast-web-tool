#!/usr/bin/env python3
import os
import sys
import json
import logging
from datetime import datetime
import gwaslab as gl

def setup_logging():
    # Logger to print info and error messages to console
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s - %(levelname)s - %(message)s',
        handlers=[logging.StreamHandler(sys.stdout)]
    )
    return logging.getLogger(__name__)

def get_reference_paths(genome_build, use_snpid_table, use_dbsnp):
    """
    Determine reference files to use based on genome build and annotation method chosen.

    Parameters:
    - genome_build: '19' for hg19/GRCh37, '38' for hg38/GRCh38
    - use_snpid_table: True to use SNPID-rsID reference table (tsv)
    - use_dbsnp: True to use dbSNP VCF files
    
    Returns tuple:
    - ref_tsv: Path to SNPID-rsID reference table (TSV) or None
    - ref_vcf: Path to dbSNP VCF file or None
    - chr_dict: Chromosome dictionary mapping for VCF processing or None
    """
    ref_tsv = None
    ref_vcf = None
    chr_dict = None

    # SNPID-rsID reference table paths inside GWASLab data
    if use_snpid_table:
        if genome_build == '19':
            ref_tsv = gl.get_path("1kg_dbsnp151_hg19")  # hg19 SNPID table
        elif genome_build == '38':
            ref_tsv = gl.get_path("1kg_dbsnp151_hg38")  # hg38 SNPID table

    # dbSNP VCF file locations should exist on the server
    if use_dbsnp:
        if genome_build == '19':
            ref_vcf = "/home/biomedinfo/gwaslab_env/data/dbsnp/GCF_000001405.25.vcf.gz"
            chr_dict = gl.get_number_to_NC(build="19")  # Chromosome dictionary for GRCh37/hg19
        elif genome_build == '38':
            ref_vcf = "/home/biomedinfo/gwaslab_env/data/dbsnp/GCF_000001405.40.vcf.gz"
            chr_dict = gl.get_number_to_NC(build="38")  # Chromosome dictionary for GRCh38/hg38

    return ref_tsv, ref_vcf, chr_dict

def assign_rsid(input_file, output_file, genome_build, use_snpid, use_dbsnp, input_fmt):
    """
    Assign rsIDs to variants in summary statistics using selected annotation methods.

    Parameters:
    - input_file: Path to input GWAS summary stats file
    - output_file: Path to save output file with assigned rsIDs
    - genome_build: '19' or '38' to specify genome build
    - use_snpid: Boolean to use SNPID-rsID reference table
    - use_dbsnp: Boolean to use dbSNP VCF annotation
    - input_fmt: Input file format for loading ('auto', 'csv', 'tsv', etc.)
    """
    logger = setup_logging()
    try:
        logger.info(f"Loading summary stats: {input_file} (format={input_fmt})")
        mysum = gl.Sumstats(input_file, fmt=input_fmt)

        logger.info("Running basic quality checks and standardization...")
        mysum.basic_check()

        ref_tsv, ref_vcf, chr_dic = get_reference_paths(genome_build, use_snpid, use_dbsnp)

        # Validate existence of dbSNP VCF if used
        if use_dbsnp and ref_vcf and not os.path.exists(ref_vcf):
            logger.warning(f"dbSNP VCF file not found: {ref_vcf} - skipping VCF annotation")
            ref_vcf = None

        logger.info("Assigning rsIDs using selected references...")
        params = {}
        if ref_tsv:
            params['ref_rsid_tsv'] = ref_tsv
            logger.info(f"Using SNPID table: {ref_tsv}")
        if ref_vcf:
            params['ref_rsid_vcf'] = ref_vcf
            params['chr_dict'] = chr_dic
            logger.info(f"Using dbSNP VCF file: {ref_vcf}")

        # Call GWASLab's assign_rsid with prepared parameters, using 2 cores by default
        mysum.assign_rsid(n_cores=2, **params)

        # Save annotated results (comma or tab separated based on output file extension)
        sep = '\t' if output_file.endswith(('.tsv', '.txt')) else ','
        mysum.data.to_csv(output_file, index=False, sep=sep)
        
        
        # Generate and save summary report using GWASLab's built-in summary() method (returns DataFrame)
        summary_df = mysum.summary()

        # Convert summary DataFrame to string for writing to file
        summary_str = summary_df.to_string()

        report_path = output_file.rsplit('.', 1)[0] + '_summary.txt'
        with open(report_path, 'w') as repfile:
            repfile.write(f"rsID Assignment Summary\nDate: {datetime.now()}\n")
            repfile.write(f"Input: {input_file}\nOutput: {output_file}\nGenome build: {genome_build}\n")
            repfile.write(f"Used SNP ID table: {use_snpid}\nUsed dbSNP VCF: {use_dbsnp}\n\n")
            repfile.write(summary_str)
            
        # Save GWASLab detailed log to *.log file
        log_path = output_file.rsplit('.', 1)[0] + '.log'
        mysum.log.save(log_path)
        logger.info(f"Process log saved: {log_path}")


        logger.info("rsID assignment completed successfully")
        logger.info(f"Summary report saved to: {report_path}")
        print(f"RESULT_JSON: {{'status':'success', 'output_file':'{output_file}', 'report':'{report_path}', 'log':'{log_path}'}}")

    except Exception as e:
        logger.error(f"Error during rsID assignment: {e}")
        print(f"RESULT_JSON: {{'status':'error', 'message':'{e}'}}")
        sys.exit(1)



def main():
    
    if len(sys.argv) < 2:
        print("Usage: assign_rsid.py <config.json>", file=sys.stderr)
        sys.exit(1)

    config_file = sys.argv[1]

    # Load JSON config
    try:
        with open(config_file, "r") as f:
            params = json.load(f)
    except Exception as e:
        print(f"Error loading config file {config_file}: {e}", file=sys.stderr)
        sys.exit(1)
        

    use_snpid = (params["params"].get("use_snpid_table", "off") in ["on", "true", True])
    use_dbsnp = (params["params"].get("use_dbsnp_vcf", "off") in ["on", "true", True])  # update according to form

    assign_rsid(
        input_file=params["input_file"],
        output_file=params["output_file"],
        genome_build=params["params"]["genome_build"],
        use_snpid=use_snpid,
        use_dbsnp=use_dbsnp,
        input_fmt=params.get("format", "auto")
    )

if __name__ == '__main__':
    main()

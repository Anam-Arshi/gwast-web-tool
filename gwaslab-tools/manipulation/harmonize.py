#!/usr/bin/env python3
"""
GWASLab Harmonization Script
This script harmonizes GWAS summary statistics using GWASLab package.
"""

import gwaslab as gl
import argparse
import sys
import os
import traceback
from datetime import datetime

def main():
    parser = argparse.ArgumentParser(description='GWASLab Harmonize summary statistics.')
    parser.add_argument('--input', required=True, help='Input summary statistics file')
    parser.add_argument('--output', required=True, help='Output harmonized summary statistics file')
    parser.add_argument('--basic_check', type=str, default='True', help='Perform basic quality check (True/False)')
    parser.add_argument('--remove_dup', type=str, default='False', help='Remove duplicate variants (True/False)')
    parser.add_argument('--genome_build', type=str, default='hg19', help='Genome build: hg19 or hg38')
    parser.add_argument('--population', type=str, default='EUR', help='Population reference: EUR, EAS, AFR, AMR, SAS')
    parser.add_argument('--maf_threshold', type=float, default=0.35, help='MAF threshold for palindromic SNP inference')
    parser.add_argument('--daf_tolerance', type=float, default=0.15, help='DAF tolerance for allele frequency difference')
    parser.add_argument('--remove_problematic', type=str, default='False', help='Remove problematic variants (True/False)')

    args = parser.parse_args()

    try:
        # Convert string arguments to boolean
        basic_check = args.basic_check.lower() == 'true'
        remove_dup = args.remove_dup.lower() == 'true'
        remove_problematic = args.remove_problematic.lower() == 'true'

        print(f"[{datetime.now()}] Starting harmonization...")
        print(f"Input file: {args.input}")
        print(f"Parameters:")
        print(f"  - Basic check: {basic_check}")
        print(f"  - Remove duplicates: {remove_dup}")
        print(f"  - Genome build: {args.genome_build}")
        print(f"  - Population: {args.population}")
        print(f"  - MAF threshold: {args.maf_threshold}")
        print(f"  - DAF tolerance: {args.daf_tolerance}")
        print(f"  - Remove problematic: {remove_problematic}")

        # Check if input file exists
        if not os.path.exists(args.input):
            raise FileNotFoundError(f"Input file not found: {args.input}")

        # Load summary statistics
        print("Loading summary statistics...")
        mysumstats = gl.Sumstats(args.input, fmt="auto")
        
        print(f"Loaded {len(mysumstats.data)} variants from input file")

        # Remove duplicates if requested
        if remove_dup:
            print("Removing duplicate variants...")
            original_count = len(mysumstats.data)
            mysumstats.remove_dup()
            new_count = len(mysumstats.data)
            print(f"Removed {original_count - new_count} duplicate variants")

        # Get reference files based on genome build and population
        print("Setting up reference files...")
        if args.genome_build == 'hg19':
            ref_seq = gl.get_path("ucsc_genome_hg19")
            if args.population == "EUR":
                ref_infer = gl.get_path("1kg_eur_hg19")
            elif args.population == "EAS":
                ref_infer = gl.get_path("1kg_eas_hg19")
            elif args.population == "AFR":
                ref_infer = gl.get_path("1kg_afr_hg19")
            elif args.population == "AMR":
                ref_infer = gl.get_path("1kg_amr_hg19")
            elif args.population == "SAS":
                ref_infer = gl.get_path("1kg_sas_hg19")
            else:
                print(f"Warning: Unknown population {args.population}, defaulting to EUR")
                ref_infer = gl.get_path("1kg_eur_hg19")
        elif args.genome_build == 'hg38':
            ref_seq = gl.get_path("ucsc_genome_hg38")
            if args.population == "EUR":
                ref_infer = gl.get_path("1kg_eur_hg38")
            elif args.population == "EAS":
                ref_infer = gl.get_path("1kg_eas_hg38")
            elif args.population == "AFR":
                ref_infer = gl.get_path("1kg_afr_hg38")
            elif args.population == "AMR":
                ref_infer = gl.get_path("1kg_amr_hg38")
            elif args.population == "SAS":
                ref_infer = gl.get_path("1kg_sas_hg38")
            else:
                print(f"Warning: Unknown population {args.population}, defaulting to EUR")
                ref_infer = gl.get_path("1kg_eur_hg38")
        else:
            raise ValueError(f"Unsupported genome build: {args.genome_build}")

        print(f"Using genome build: {args.genome_build}")
        print(f"Using population reference: {args.population}")
        print(f"Reference sequence file: {ref_seq}")
        print(f"Reference inference VCF: {ref_infer}")

        # Perform harmonization
        print("Starting harmonization process...")
        mysumstats.harmonize(
            basic_check=basic_check,
            ref_seq=ref_seq,
            ref_infer=ref_infer,
            ref_alt_freq="AF",
            maf_threshold=args.maf_threshold,
            daf_tolerance=args.daf_tolerance,
            remove=remove_problematic,
            n_cores=3
        )

        print("Harmonization completed successfully!")

        # Print summary information
        print("\nHarmonization Summary:")
        try:
            summary = mysumstats.summary()
            if summary is not None:
                print(summary)
        except Exception as e:
            print(f"Could not generate summary: {str(e)}")

        # Print status information
        print("\nStatus Code Summary:")
        try:
            status_lookup = mysumstats.lookup_status()
            if status_lookup is not None:
                print(status_lookup)
        except Exception as e:
            print(f"Could not generate status lookup: {str(e)}")

        # Create output directory if it doesn't exist
        output_dir = os.path.dirname(args.output)
        if output_dir and not os.path.exists(output_dir):
            os.makedirs(output_dir)

        # Save harmonized results
        print(f"Saving harmonized results to: {args.output}")
        mysumstats.to_format(path=args.output, fmt="gwaslab")

        # Create additional output files
        base_name = os.path.splitext(args.output)[0]
        
        # Save harmonization log
        log_file = f"{base_name}_harmonization_log.txt"
        try:
            with open(log_file, 'w') as f:
                f.write(f"GWASLab Harmonization Log\n")
                f.write(f"Generated: {datetime.now()}\n")
                f.write("="*50 + "\n\n")
                f.write(f"Input file: {args.input}\n")
                f.write(f"Output file: {args.output}\n")
                f.write(f"Genome build: {args.genome_build}\n")
                f.write(f"Population reference: {args.population}\n")
                f.write(f"Basic check: {basic_check}\n")
                f.write(f"Remove duplicates: {remove_dup}\n")
                f.write(f"MAF threshold: {args.maf_threshold}\n")
                f.write(f"DAF tolerance: {args.daf_tolerance}\n")
                f.write(f"Remove problematic: {remove_problematic}\n\n")
                
                # Add data summary
                f.write("Data Summary:\n")
                f.write(f"Total variants after harmonization: {len(mysumstats.data)}\n")
                
                # Count status codes
                if 'STATUS' in mysumstats.data.columns:
                    status_counts = mysumstats.data['STATUS'].value_counts()
                    f.write("\nStatus Code Counts:\n")
                    for status, count in status_counts.items():
                        f.write(f"  {status}: {count}\n")
            
            print(f"Harmonization log saved to: {log_file}")
            
        except Exception as e:
            print(f"Warning: Could not create log file: {str(e)}")

        # Save status summary if available
        try:
            status_file = f"{base_name}_status_summary.tsv"
            status_summary = mysumstats.lookup_status()
            if status_summary is not None:
                status_summary.to_csv(status_file, sep='\t', index=False)
                print(f"Status summary saved to: {status_file}")
        except Exception as e:
            print(f"Warning: Could not create status summary file: {str(e)}")

        print(f"\n[{datetime.now()}] Harmonization completed successfully!")
        print(f"Final variant count: {len(mysumstats.data)}")
        
    except Exception as e:
        print(f"Error during harmonization: {str(e)}")
        print(f"Traceback: {traceback.format_exc()}")
        sys.exit(1)

if __name__ == "__main__":
    main()

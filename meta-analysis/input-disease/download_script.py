import os
import requests
import yaml
import json
from bs4 import BeautifulSoup
from concurrent.futures import ThreadPoolExecutor, as_completed


def update_progress(job_folder, gcst_id, filename, percent, status):
    """Update progress.json to track progress of multiple files cumulatively"""
    progress_file = os.path.join(job_folder, "progress.json")

    # Load existing progress data or initialize empty dictionary
    if os.path.exists(progress_file):
        with open(progress_file, "r") as f:
            try:
                progress_data = json.load(f)
            except json.JSONDecodeError:
                progress_data = {}
    else:
        progress_data = {}

    # Use GCST ID as a key to group progress for files under that ID
    if gcst_id not in progress_data:
        progress_data[gcst_id] = {}

    # Update progress for the current file
    progress_data[gcst_id][filename] = {
        "percent": percent,
        "status": status
    }

    # Write the updated progress data back to the JSON file
    with open(progress_file, "w") as f:
        json.dump(progress_data, f, indent=4)


def download_file(url, dest_folder, gcst_id, job_folder):
    local_filename = os.path.basename(url)
    dest_path = os.path.join(dest_folder, local_filename)

    print(f"Downloading: {url}")
    with requests.get(url, stream=True) as r:
        r.raise_for_status()
        total_size = int(r.headers.get("Content-Length", 0))
        downloaded = 0
        chunk_size = 1024 * 1024  # 1MB chunks

        with open(dest_path, "wb") as f:
            for chunk in r.iter_content(chunk_size=chunk_size):
                if chunk:
                    f.write(chunk)
                    downloaded += len(chunk)
                    if total_size > 0:
                        percent = int(downloaded * 100 / total_size)
                        update_progress(job_folder, gcst_id, local_filename, percent, "downloading")

    update_progress(job_folder, gcst_id, local_filename, 100, "done")
    return dest_path


def parse_yaml(yaml_path):
    with open(yaml_path, 'r') as f:
        y = yaml.safe_load(f)
    return y.get('genome_assembly'), y.get('file_type')


def list_directory(base_url):
    """Fetch directory listing and return all file links"""
    print(f"Listing directory: {base_url}")
    r = requests.get(base_url + "/")
    r.raise_for_status()
    soup = BeautifulSoup(r.text, "html.parser")
    links = [a['href'] for a in soup.find_all('a', href=True)]
    return links


def process_gcst(entry, job_folder):
    """Worker function for one GCST ID"""
    base_url = entry['stats_url'].rstrip("/")
    gcst_id = os.path.basename(base_url)

    file_builds = {}
    all_files = []

    try:
        files = list_directory(base_url)
    except Exception as e:
        print(f"Failed to list directory {base_url}: {e}")
        return all_files, file_builds

    # find main summary stats file
    stats_candidates = [f for f in files if f.endswith((".tsv.gz", ".txt.gz", ".tsv")) and f.startswith(gcst_id)]
    if not stats_candidates:
        print(f"No stats files found in {base_url}")
        return all_files, file_builds

    stats_file = stats_candidates[0]
    stats_url = f"{base_url}/{stats_file}"

    try:
        stats_local = download_file(stats_url, job_folder, gcst_id, job_folder)
        all_files.append(os.path.basename(stats_url))
    except Exception as e:
        print(f"Failed to download {stats_url}: {e}")
        return all_files, file_builds

    # look for matching YAML
    yaml_file = stats_file + "-meta.yaml"
    if yaml_file in files:
        yaml_url = f"{base_url}/{yaml_file}"
        try:
            yaml_local = download_file(yaml_url, job_folder, gcst_id, job_folder)
            build, filetype = parse_yaml(yaml_local)
            if build is not None:
                # Remove 'GRCh' prefix if present and keep only the number
                build_number = str(build)
                if build_number.startswith("GRCh"):
                    build_number = build_number[4:]  # strip first 4 characters 'GRCh'

                # Check if the build is '37' and set it to '19'
                if build_number == "37":
                    build_number = "19"

                file_builds[os.path.basename(stats_url)] = build_number
        except Exception as e:
            print(f"Failed to download or parse YAML {yaml_url}: {e}")
    else:
        print(f"No YAML file found for {stats_file}")

    return all_files, file_builds


def main(summary_files, job_folder, max_workers=4):
    if not os.path.isdir(job_folder):
        raise ValueError(f"Job folder does not exist: {job_folder}")

    all_files = []
    all_builds = {}

    with ThreadPoolExecutor(max_workers=max_workers) as executor:
        futures = [executor.submit(process_gcst, entry, job_folder) for entry in summary_files]
        for future in as_completed(futures):
            files, builds = future.result()
            all_files.extend(files)
            all_builds.update(builds)

    # Write job_config.json in the new structured format with fixed "format" as "ssf"
    structured_files = []
    for f in all_files:
        genome_build = all_builds.get(f, "38")  # default to 38
        structured_files.append({
            "filename": f,
            "genome_build": genome_build,
            "format": "ssf"
        })

    job_config = {
        "job_id": os.path.basename(job_folder),
        "files": structured_files
    }
    config_path = os.path.join(job_folder, "job_config.json")
    with open(config_path, 'w') as f:
        json.dump(job_config, f, indent=4)

    print(f"Job config file saved to {config_path}")


if __name__ == "__main__":
    import sys
    if len(sys.argv) != 2:
        print("Usage: python download_script.py <job_folder>")
        sys.exit(1)

    job_folder = sys.argv[1]
    json_file = os.path.join(job_folder, 'studies.json')
    with open(json_file, 'r') as f:
        summary_files = json.load(f)
    main(summary_files, job_folder, max_workers=4)

<?php
include("../../header.php");
$jobId = uniqid("job_");
$_SESSION['job_id'] = $jobId;
?>
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" />
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<style>
    .dropzone {
        background: #f3ede6;
        border: 2px dashed #a1887f;
        color: #5d4037;
        padding: 30px;
    }
    .dz-preview .dz-remove {
        color: #8d6e63;
    }
    #fileDetailsTable {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        color: #5d4037;
    }
    #fileDetailsTable th, #fileDetailsTable td {
        border: 1px solid #a1887f;
        padding: 8px 12px;
        text-align: left;
        vertical-align: middle;
    }
    #fileDetailsTable th {
        background-color: #f3ede6;
    }
    .form-select-sm {
        font-size: 0.875rem;
        padding: 4px 8px;
        border-radius: 4px;
        border: 1px solid #a1887f;
        color: #5d4037;
        background: #fff;
    }
    #fileFormatSelect {
        width: 100%;
        max-width: 140px;
    }
    #proceedBtn {
        background-color: #6a4d35;
        color: #fff;
        border: none;
        padding: 10px 16px;
        border-radius: 6px;
        margin-top: 20px;
        display: none;
    }
    #uploadStatus {
        font-weight: bold;
        color: #5d4037;
        margin-top: 15px;
    }
    #userMessages {
        margin-top: 30px;
        background-color: #f9f4ef;
        border-left: 5px solid #a1887f;
        padding: 15px 20px;
        color: #5d4037;
        font-size: 0.9rem;
        line-height: 1.4;
    }
</style>

<div class="container my-5">
    <h2 class="mb-3">Upload GWAS Summary Statistics</h2>
    <p class="mb-3">
        You can upload up to 5 GWAS summary files (<code>.tsv</code>, <code>.txt</code>, <code>.csv</code>, <code>.gz</code>).
    </p>
    <ul>
        <li><strong>Supported formats:</strong>  auto (auto-detected), SSF, METAL, PLINK, SAIGE, Regenie, fastGWA, LDSC, and many other common GWAS formats.</li>
        <li><strong>Required columns:</strong> <code>rsid</code>/<code>variant_id</code>, <code>chromosome</code>, <code>base_pair_location</code>,
            and effect size with standard error (<code>beta</code> &amp; <code>standard_error</code>) or odds ratio with confidence interval (<code>OR</code> &amp; <code>CI</code>), plus <code>p_value</code>.
        </li>
        <li><strong>Optional columns:</strong> other metadata.</li>
    </ul>

    <form action="handle_upload.php" class="dropzone" id="gwasUpload" enctype="multipart/form-data" method="post"></form>

    <table id="fileDetailsTable" style="display:none;">
        <thead>
            <tr>
                <th>File Name</th>
                <th>Genome Build</th>
                <th>File Format</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="fileDetailsBody">
            <!-- Rows will be added dynamically -->
        </tbody>
    </table>

    <div id="uploadStatus" class="mt-4 text-muted">Upload files...</div>
    <button id="proceedBtn">Proceed to Initial QC</button>
    <div id="processingInfo" style="margin-top:30px; background-color:#f9f4ef; border-left:4px solid #8d6e63; padding:15px 20px; color:#5d4037;">
    <strong>What happens next?</strong>
    <ul style="margin-top:8px; margin-bottom:0;">
        <li>Uploaded files will undergo <b>standardization</b> and <b>normalization</b> of columns and data.</li>
        <li><b>Duplicate</b> variants and those with <b>multi-allelic</b> records will be automatically removed.</li>
    </ul>
    <p style="margin-top: 12px;">
      These processes help ensure the reliability and consistency of your summary statistics before deeper analyses.
    </p>
</div>


   <!-- <div id="userMessages">
        <p><strong>Please specify the genome build of each file (e.g., GRCh37/hg19, GRCh38/hg38).</strong></p>
        <p>For reliable meta-analysis, upload files using the same genome build whenever possible.</p>
        <p>If your files use different builds, you can use the liftover tool available in the ‘Genome Build Conversion’ section before running meta-analysis.</p>
        <p>Regarding harmonization: Harmonizing with the reference population is a computationally intensive task. You can perform this beforehand in the tool’s manipulation section. <a href="manipulation.php" target="_blank">Go to Manipulation Section</a>.</p>
        <p></p>
    </div> -->
</div>

<script>
Dropzone.options.gwasUpload = {
    url: "handle_upload.php",
    paramName: "file",
    maxFiles: 5,
    maxFilesize: 3000, // MB
    acceptedFiles: ".tsv,.csv,.txt,.zip,.gz",
    autoProcessQueue: true,
    addRemoveLinks: true,
    init: function () {
        const dz = this;
        const proceedBtn = document.getElementById("proceedBtn");
        const statusText = document.getElementById("uploadStatus");
        const fileDetailsTable = document.getElementById("fileDetailsTable");
        const fileDetailsBody = document.getElementById("fileDetailsBody");

        // Tracks file info by server filename or upload.uuid
        const filesInfoMap = {};

        // Utility to add file row to table
        function addFileRow(file, serverFileName) {
            fileDetailsTable.style.display = "table";
            const row = document.createElement("tr");
            row.id = "row_" + file.upload.uuid;

            // File name cell (text)
            const nameCell = document.createElement("td");
            nameCell.textContent = file.name;
            row.appendChild(nameCell);

            // Genome Build - select input cell
            const buildCell = document.createElement("td");
            const buildSelect = document.createElement("select");
            buildSelect.className = "form-select-sm";
            buildSelect.innerHTML = `
                <option value="38" selected>hg38 (GRCh38)</option>
                <option value="19">hg19 (GRCh37)</option>
            `;
            buildCell.appendChild(buildSelect);
            row.appendChild(buildCell);

            // File Format - select input cell
            const formatCell = document.createElement("td");
            const formatSelect = document.createElement("select");
            formatSelect.className = "form-select-sm";
            formatSelect.id = "fileFormatSelect_" + file.upload.uuid;
            formatSelect.innerHTML = `
                <option value="" selected disabled>Select format</option>
                <option value="auto">auto</option>
                <option value="ssf">SSF</option>
                <option value="METAL">METAL</option>
                <option value="PLINK">PLINK</option>
                <option value="SAIGE">SAIGE</option>
                <option value="Regenie">Regenie</option>
                <option value="fastGWA">fastGWA</option>
                <option value="LDSC">LDSC</option>
                <option value="Other">Other</option>
            `;
            formatCell.appendChild(formatSelect);
            row.appendChild(formatCell);

            // Action cell (delete)
            const actionCell = document.createElement("td");
            const removeLink = document.createElement("a");
            removeLink.href = "#";
            removeLink.textContent = "Remove";
            removeLink.style.color = "#8d6e63";
            removeLink.addEventListener("click", function(e) {
                e.preventDefault();
                dz.removeFile(file);
            });
            actionCell.appendChild(removeLink);
            row.appendChild(actionCell);

            fileDetailsBody.appendChild(row);

            // Store ref for later use in sending data
            filesInfoMap[file.upload.uuid] = {
                buildSelect: buildSelect,
                formatSelect: formatSelect,
                rowElement: row,
                serverFileName: serverFileName || null
            };
        }

        this.on("sending", function(file, xhr, formData) {
            let fileKey = file.serverFileName || file.upload.uuid;
            const info = filesInfoMap[file.upload.uuid] || {};
            // Append genome_build from user selection if available, default '38'
            let build = "38";
            if (info.buildSelect) {
                build = info.buildSelect.value || "38";
            }
            formData.append("genome_build", build);

            // Append format from user selection if available, else empty string
            let format = "";
            if (info.formatSelect) {
                format = info.formatSelect.value || "";
            }
            formData.append("file_format", format);

            formData.append("job_id", "<?= $jobId ?>");
        });

        this.on("addedfile", function(file) {
            statusText.innerText = "Uploading...";
            addFileRow(file); // add row with UUID key until serverFileName known
        });

        this.on("removedfile", function(file) {
            // Remove row from table
            let rowId = "row_" + file.upload.uuid;
            const row = document.getElementById(rowId);
            if (row) {
                row.remove();
            }
            // Remove from map
            delete filesInfoMap[file.upload.uuid];

            // Tell backend to delete file by serverFileName or original name
            fetch("delete_uploaded_file.php", {
                method: "POST",
                body: JSON.stringify({
                    job_id: "<?= $jobId ?>",
                    file_name: file.serverFileName || file.name
                }),
                headers: {
                    "Content-Type": "application/json"
                }
            })
            .then(res => res.json())
            .then(data => {
                console.log("Delete response:", data);
            })
            .catch(err => console.error("Delete error:", err));

            // If no files remain, hide the table
            if (dz.files.length === 0) {
                fileDetailsTable.style.display = "none";
                statusText.innerText = "Waiting for files...";
                proceedBtn.style.display = "none";
            }
        });

        this.on("success", function(file, response) {
            try {
                const res = typeof response === "string" ? JSON.parse(response) : response;
                if (res.success && res.file) {
                    file.serverFileName = res.file;

                    // Update file info to use serverFileName key to keep data in sync
                    const info = filesInfoMap[file.upload.uuid];
                    if (info) {
                        info.serverFileName = res.file;
                    }
                }
            } catch (e) {
                console.error("Error parsing upload response", e);
            }
        });

        this.on("queuecomplete", function () {
            statusText.innerText = "Upload complete.";
            proceedBtn.style.display = "inline-block";
        });

        proceedBtn.addEventListener("click", function () {
            // Validate that all files have a selected format
            for (const key in filesInfoMap) {
                const formatSelect = filesInfoMap[key].formatSelect;
                if (!formatSelect.value) {
                    alert("Please select the format for all uploaded files before proceeding.");
                    return;
                }
            }
            proceedBtn.disabled = true;
            proceedBtn.innerText = "Redirecting...";

            // Prepare mapping of files with their genome builds and formats
            const fileBuildFormatMap = {};
            for (const key in filesInfoMap) {
                const info = filesInfoMap[key];
                const fileKey = info.serverFileName || key;
                fileBuildFormatMap[fileKey] = {
                    genome_build: info.buildSelect.value || "38",
                    file_format: info.formatSelect.value || ""
                };
            }
            // Send mapping json to backend
            fetch("job_manager.php", {
                method: "POST",
                body: new URLSearchParams({
                    job_id: "<?= $jobId ?>",
                    file_builds_formats: JSON.stringify(fileBuildFormatMap)
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = "gwaslab_status.php?job=" + data.job_id;
                } else {
                    alert("Error: " + data.error);
                    proceedBtn.disabled = false;
                    proceedBtn.innerText = "Proceed to Initial QC";
                }
            })
            .catch(err => {
                alert("Request failed: " + err.message);
                proceedBtn.disabled = false;
                proceedBtn.innerText = "Proceed to Initial QC"; // Proceed to standardization & QC
            });
        });
    }
};
</script>
<?php include("../../footer.php"); ?>
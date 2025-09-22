<?php
// expects $jobId or $jobId_safe from parent
$jobId = isset($jobId) ? $jobId : (isset($jobId_safe) ? $jobId_safe : null);
if (!$jobId) { echo "<p style='color:red;'>Missing job id.</p>"; return; }

$jobDir = "user_uploads/$jobId";
$configFile = "$jobDir/job_config.json";

if (!file_exists($configFile)) {
  echo "<h3>Meta-analysis Options</h3>";
  echo "<p style='color:#b00020;'>Cannot find <code>job_config.json</code> for this job. Meta-analysis table cannot be built.</p>";
  return;
}

$config = json_decode(file_get_contents($configFile), true);
$files = $config['files'] ?? [];
$fileBuilds = [];
foreach ($files as $fileEntry) {
    $filename = $fileEntry['filename'] ?? '';
    $build = $fileEntry['genome_build'] ?? 'Unknown';

    if ($filename) {
        $fileBuilds[$filename] = $build;
    }
}

$builds = array_unique(array_values($fileBuilds));
$allSameBuild = count($builds) === 1;
$currentBuild = $allSameBuild ? $builds[0] : null;
?>

<style>
  table.meta-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 12px;
  }
  table.meta-table thead tr {
    background: #f9f5f0;
;
  }
  table.meta-table th, table.meta-table td {
    padding: 10px 12px;
    text-align: left;
    vertical-align: middle;
  }
  table.meta-table th:first-child {
    border-top-left-radius: 12px;
  }
  table.meta-table th:last-child {
    border-top-right-radius: 12px;
  }
  table.meta-table tbody tr {
    background: #fff;
    border: 1px solid #e6dacd;
    border-radius: 10px;
    box-shadow: 0 0 3px rgba(0,0,0,0.05);
  }
  .info-icon {
    display: inline-block;
    margin-left: 6px;
    color: #888;
    border: 1px solid #bbb;
    font-weight: bold;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 14px;
    line-height: 16px;
    text-align: center;
    cursor: help;
    position: relative;
  }
  .info-icon:hover .tooltip {
    visibility: visible;
    opacity: 1;
  }
  .tooltip {
    visibility: hidden;
    opacity: 0;
    background: #faebe6;
    color: #7f5539;
    text-align: left;
    border-radius: 8px;
    padding: 10px 14px;
    position: absolute;
    z-index: 1000;
    width: 260px;
    top: 22px;
    left: 50%;
    transform: translateX(-50%);
    box-shadow: 0 0 8px rgba(0,0,0,0.12);
    transition: opacity 0.3s;
    font-size: 13px;
  }
  .tooltip::after {
    content: "";
    position: absolute;
    bottom: 100%;
    left: 50%;
    margin-left: -7px;
    border-width: 7px;
    border-style: solid;
    border-color: transparent transparent #faebe6 transparent;
  }
  .form-select {
    min-width: 220px;
    padding: 6px 10px;
    font-size: 14px;
    border-radius: 6px;
    border: 1px solid #ccc;
    max-width: 350px;
  }
.btn {
  padding: 8px 18px;
  border-radius: 6px;
  border: none;
  background-color: #7a5c3e;  /* Original brown color */
  color: white;
  cursor: pointer;
  font-size: 15px;
}
.btn:hover {
  background-color: #6e5132;  /* Slightly darker brown on hover */
}

  .meta-description {
    margin-top: 4px;
    font-size: 13px;
    color: #554136;
  }
  .ancestry-select {
  display: inline-block;
}

</style>

<h3 style="margin-top:0;">Meta-analysis Options</h3>

<div style="display:flex; flex-direction:column; gap:5px; margin-bottom:20px; margin-top:20px;">
  <div style="display:flex; gap:24px; align-items:center;">
    <label style="font-weight:500;">
      <input type="checkbox" id="model_fixed" checked style="margin-right:6px;"> Fixed Effect Model
    </label>
    <label style="font-weight:500;">
      <input type="checkbox" id="model_random" checked style="margin-right:6px;"> Random Effect Model
    </label>
  </div>
  <div class="meta-description" style="margin-left:2px; margin-top: 3px; margin-bottom:8px;">
    Perform meta-analysis using inverse-variance weighting with fixed and random effect models.
  </div>


  <div>
    <label for="tau2Method"><strong>τ² method</strong></label><br>
    <select id="tau2Method" class="form-select">
      <option value="DL" selected>DerSimonian–Laird (DL)</option>
      <option value="REML">REML</option>
      <option value="ML">Maximum Likelihood (ML)</option>
      <option value="SJ">Sidik–Jonkman (SJ)</option>
      <option value="HE">Hedges (HE)</option>
    </select>
    <div class="meta-description">
      Statistical method to estimate between-study variance (heterogeneity) in meta-analysis.
    </div>
  </div>

  <?php if (!$allSameBuild): ?>
    <div>
      <label for="target_build"><strong>Target Build for Liftover</strong></label><br>
      <select id="target_build" class="form-select">
        <option value="38" selected>GRCh38 (hg38)</option>
        <option value="19">GRCh37 (hg19)</option>
      </select>
      <div style="color:#555; font-size:13px; margin-top:4px;">
        Any study not on the selected target build will be automatically lifted over and harmonized.
      </div>
    </div>
  <?php endif; ?>
</div>

<?php if (count($files) < 2): ?>
  <p style="color:#7a6a60;">Waiting for at least two processed studies to enable meta-analysis…</p>
  <?php return; ?>
<?php endif; ?>
<h4 style="margin-bottom:10px;">Select Files for Meta-analysis</h4>

<div style="overflow-x:auto;">
  <table class="meta-table" id="meta-select-table">
<thead>
  <tr>
    <th style="width:50px;">Select</th>
    <th>Filename</th>
    <th>Build</th>
    <?php if (!$allSameBuild): ?><th style="width:80px;">Liftover</th><?php endif; ?>
    <th style="min-width:250px;">Harmonize
      <span class="info-icon" tabindex="0" aria-label="Info about harmonization">?
        <div class="tooltip">
          Harmonization aligns variants to a reference genome, standardizes variant info, and resolves strand ambiguities. This process is resource intensive and may take some time.
        </div>
      </span>
    </th>
    <th style="min-width:150px;">Reference Population
      <span class="info-icon" tabindex="0" aria-label="Info about reference population">?
        <div class="tooltip">
          Used for strand inference of palindromic SNPs during harmonization.
        </div>
      </span>
    </th>
  </tr>
</thead>


    <tbody>
      <?php foreach ($fileBuilds as $f => $b): ?>
        <tr>
          <td><input type="checkbox" class="meta-select" value="<?= htmlspecialchars($f) ?>"></td>
          <td><?= htmlspecialchars($f) ?></td>
          <td data-build="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></td>
          <?php if (!$allSameBuild): ?>
            <td style="text-align:center;"><input type="checkbox" class="liftover-checkbox" disabled></td>
          <?php endif; ?>

             <td>
            <input type="checkbox" class="harmonize-checkbox" id="harmonize-<?= md5($f) ?>">
            
        
          </td>
                    
          <td>
            <select class="form-select ancestry-select" disabled style="width: 190px;">
              <option value="None" selected>None</option>
              <option value="eur">EUR (European)</option>
              <option value="eas">EAS (East Asian)</option>
              <option value="afr">AFR (African)</option>
              <option value="amr">AMR (American)</option>
              <option value="sas">SAS (South Asian)</option>
            </select>
          </td>

       

        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div style="display:flex;gap:12px;margin-top:18px;align-items:center;">
  <button id="run-meta-btn" class="btn">Run Meta-analysis</button>
  <span id="meta-status" style="font-weight:600;"></span>
</div>

<script>
  (() => {
    const allSame = <?= $allSameBuild ? 'true' : 'false' ?>;
    const targetBuildSelect = document.getElementById('target_build');
    
    function applyLiftoverDefaults() {
      if (allSame) return;

      const targetBuild = targetBuildSelect.value;

      document.querySelectorAll('#meta-select-table tbody tr').forEach(row => {
        const build = row.querySelector('td[data-build]').dataset.build;
        const liftover = row.querySelector('.liftover-checkbox');
        const harmonize = row.querySelector('.harmonize-checkbox');
        const ancestrySelect = row.querySelector('.ancestry-select');

        if (!build || build === 'Unknown') {
          liftover.checked = false;
          liftover.disabled = true;
          harmonize.disabled = false;
          ancestrySelect.style.display = 'none';
          ancestrySelect.value = '';
          return;
        }

      if (build !== targetBuild) {
        liftover.checked = true;
        liftover.disabled = true;
        harmonize.checked = true;
        harmonize.disabled = true;
        ancestrySelect.disabled = false;
      } else {
        liftover.checked = false;
        liftover.disabled = true;
        harmonize.disabled = false;
        ancestrySelect.disabled = !harmonize.checked;
        if (!harmonize.checked) ancestrySelect.value = '';
      }

      });
    }

    if (!allSame && targetBuildSelect) {
      targetBuildSelect.addEventListener('change', () => {
        applyLiftoverDefaults();
      });
    }

document.querySelectorAll('.harmonize-checkbox').forEach(cb => {
  cb.addEventListener('change', e => {
    const row = e.target.closest('tr');
    const ancestrySelect = row.querySelector('.ancestry-select');
    if (e.target.checked) {
      ancestrySelect.disabled = false;
    } else {
      // If liftover forces harmonize checked, keep population enabled
      const liftoverChk = row.querySelector('.liftover-checkbox');
      if (liftoverChk && liftoverChk.checked) {
        ancestrySelect.disabled = false;
      } else {
        ancestrySelect.disabled = true;
        ancestrySelect.value = '';
      }
    }
  });
});


    // Initial apply on page load
    applyLiftoverDefaults();

    document.getElementById('run-meta-btn').addEventListener('click', () => {
      const tau2 = document.getElementById('tau2Method')?.value || 'DL';
      const targetBuildVal = allSame ? <?= json_encode($currentBuild) ?> : (document.getElementById('target_build')?.value || '38');

      const selected = [];
      document.querySelectorAll('#meta-select-table .meta-select:checked').forEach(cb => {
        const row = cb.closest('tr');
        const file = cb.value;
        const build = row.querySelector('td[data-build]').dataset.build || 'Unknown';
        const harmonize = row.querySelector('.harmonize-checkbox').checked;
        const liftover = allSame ? false : (row.querySelector('.liftover-checkbox')?.checked || false);
        const ancestry = row.querySelector('.ancestry-select')?.value || '';

        selected.push({
          filename: file,
          build: build,
          harmonize: harmonize,
          liftover: liftover,
          population: ancestry
        });
      });

      if (selected.length < 2) {
        alert('Select at least 2 studies to run meta-analysis.');
        return;
      }

      const resultTab = window.open(`view_meta_results.php?job=${encodeURIComponent(<?= json_encode($jobId) ?>)}`, '_blank');

    const payload = {
      jobId: <?= json_encode($jobId) ?>,
      tau2: tau2,
      target_build: targetBuildVal,
      files: selected,
      model: {
        fixed: document.getElementById('model_fixed').checked,
        random: document.getElementById('model_random').checked
      }
};


      const statusSpan = document.getElementById('meta-status');
      statusSpan.textContent = 'Starting meta-analysis…';

      fetch('run-meta-job.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      })
      .then(r => r.json())
      .then(resp => {
        if (resp && resp.success) {
          statusSpan.textContent = 'Meta-analysis started.';
        } else {
          statusSpan.textContent = 'Error starting meta-analysis.';
          if (resultTab) resultTab.postMessage({ error: resp?.error || 'Unknown error'}, '*');
          alert('Error: ' + (resp?.error || 'Unknown error'));
        }
      })
      .catch(err => {
        statusSpan.textContent = 'Request failed.';
        if (resultTab) resultTab.postMessage({ error: 'Request failed'}, '*');
        alert('Failed to start meta-analysis.');
      });
    });
  })();
</script>

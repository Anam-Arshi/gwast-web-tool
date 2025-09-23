<?php include 'header.php'; ?>

<div class="container py-5">
 <p class="text-center text-muted mb-5">
    GWAST is an open access web tool for GWAS meta-analysis, post-GWAS analysis, and summary statistics processing using state-of-the-art methods and the GWASLab package.
</p>

  <h3 class="text-center mb-4" style="color: #5D4E37;">Meta-Analysis</h3>
  <div class="row g-4 justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body text-center">
          <i class="fas fa-upload fa-2x mb-3" style="color: #B99C6B;"></i>
          <h5 class="card-title mb-2">Upload Your GWAS Files</h5>
          <p class="card-text text-muted">Submit your own summary statistics for meta-analysis using fixed and random effect models.</p>
          <a href="meta-analysis/upload-files/" class="btn btn-sm btn-primary" style="background: #B99C6B; border: none;">Start</a>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body text-center">
          <i class="fas fa-disease fa-2x mb-3" style="color: #B99C6B;"></i>
          <h5 class="card-title mb-2">Start with Disease</h5>
          <p class="card-text text-muted">Explore curated GWAS datasets starting from disease names.</p>
          <a href="disease-meta.php" class="btn btn-sm btn-primary" style="background: #B99C6B; border: none;">Explore</a>
        </div>
      </div>
    </div>
  </div>

  <h3 class="text-center my-5" style="color: #5D4E37;">Post-GWAS Analysis</h3>
  <div class="row g-4 justify-content-center">
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-0">
        <img src="assets/disease.png" class="card-img-top" alt="Disease analyzer">
        <div class="card-body text-center">
          <h5 class="card-title">Disease Analyzer</h5>
          <p class="card-text text-muted">Select and filter disease-associated SNPs for downstream analysis.</p>
          <a href="post-gwas/disease-analyzer/disease-analyzer.php" class="btn btn-sm btn-outline-primary" style="color: #B99C6B; border-color: #B99C6B;">View</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-0">
        <img src="assets/snp.png" class="card-img-top" alt="SNP analyzer">
        <div class="card-body text-center">
          <h5 class="card-title">SNP Analyzer</h5>
          <p class="card-text text-muted">Examine SNP-level details and mapped gene links across studies.</p>
          <a href="post-gwas/snp-analyzer/snp-input.php" class="btn btn-sm btn-outline-primary" style="color: #B99C6B; border-color: #B99C6B;">View</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-0">
        <img src="assets/gene.png" class="card-img-top" alt="Gene analyzer">
        <div class="card-body text-center">
          <h5 class="card-title">Gene Analyzer</h5>
          <p class="card-text text-muted">Perform gene-based and pathway-level enrichment analysis.</p>
          <a href="post-gwas/gene-analyzer/gene-input.php" class="btn btn-sm btn-outline-primary" style="color: #B99C6B; border-color: #B99C6B;">View</a>
        </div>
      </div>
    </div>
  </div>

  <h3 class="text-center my-5" style="color: #5D4E37;">GWASLab Tools</h3>
<div class="row g-4 justify-content-center">
  <!-- Manipulation -->
  <div class="col-md-4">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-body text-center">
        <i class="fas fa-database fa-2x mb-3" style="color: #B99C6B;"></i>
        <h5 class="card-title">Summary Stats Manipulation</h5>
        <p class="card-text text-muted">Harmonize coordinates, convert genome builds, assign rsIDs, and standardize formats.</p>
        <a href="gwaslab_manipulation.php" class="btn btn-sm btn-outline-primary" style="color:#B99C6B; border-color:#B99C6B;">Open</a>
      </div>
    </div>
  </div>

  <!-- Utilities -->
  <div class="col-md-4">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-body text-center">
        <i class="fas fa-tools fa-2x mb-3" style="color: #B99C6B;"></i>
        <h5 class="card-title">Utilities</h5>
        <p class="card-text text-muted">Get lead/novel SNPs, infer genome build, QC helpers and more.</p>
        <a href="gwaslab_utilities.php" class="btn btn-sm btn-outline-primary" style="color:#B99C6B; border-color:#B99C6B;">Open</a>
      </div>
    </div>
  </div>

  <!-- Visualization -->
  <div class="col-md-4">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-body text-center">
        <i class="fas fa-chart-line fa-2x mb-3" style="color: #B99C6B;"></i>
        <h5 class="card-title">Visualization</h5>
        <p class="card-text text-muted">Generate Manhattan, QQ, and region plots from summary stats.</p>
        <a href="gwaslab_visualization.php" class="btn btn-sm btn-outline-primary" style="color:#B99C6B; border-color:#B99C6B;">Open</a>
      </div>
    </div>
  </div>
</div>

</div>

<?php include 'footer.php'; ?>

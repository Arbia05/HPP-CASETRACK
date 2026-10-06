<?php // includes/footer.php ?>
</div><!-- /#page-content -->
</div><!-- /#app-wrapper -->

<script>
window.APP_BASE = '<?= rtrim(getBasePath(), '/') ?>';
window.addEventListener('pageshow', function(event) {
  if (event.persisted) {
    window.location.reload();
  }
});
</script>
<script src="<?= $base ?>assets/js/app.js"></script>
<?= $extraScript ?? '' ?>
</body>
</html>

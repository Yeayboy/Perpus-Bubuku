        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var pbBurger = document.getElementById('pbBurger');
    var pbShell = document.getElementById('pbShell');
    var pbOverlay = document.getElementById('pbOverlay');
    if (pbBurger) {
        pbBurger.addEventListener('click', function () {
            pbShell.classList.toggle('pb-sidebar-open');
        });
    }
    if (pbOverlay) {
        pbOverlay.addEventListener('click', function () {
            pbShell.classList.remove('pb-sidebar-open');
        });
    }
</script>
</body>
</html>

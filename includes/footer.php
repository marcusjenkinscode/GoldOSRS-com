</main>

<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-col">
            <h3 class="gold-text">GoldOSRS</h3>
            <p>Your trusted OSRS &amp; RS3 gold and services marketplace.</p>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="/">Home</a></li>
                <li><a href="/order.php">Order</a></li>
                <li><a href="/gambling.php">Games</a></li>
                <li><a href="/raffle.php">Raffle</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Support</h4>
            <ul>
                <li><a href="mailto:support@goldosrs.com">Email Support</a></li>
                <li><a href="#">Discord</a></li>
                <li><a href="#">Terms of Service</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved. Not affiliated with Jagex Ltd.</p>
    </div>
</footer>

<script>
function toggleNav() {
    var links = document.getElementById('navLinks');
    links.classList.toggle('open');
}
</script>
</body>
</html>

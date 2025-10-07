<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

<!-- Ładujemy index.js z BASE_URL (działa poprawnie w podkatalogu) -->
<?php $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>
<script src="<?= $BASE ?>/assets/js/index.js" defer></script>


<!-- Baner cookies -->

<style>
#cookieBanner {
	display: none;
	position: fixed;
	left: 32px;
	bottom: 32px;
	z-index: 9999;
	background: #fff;
	border-radius: 18px;
	box-shadow: 0 4px 32px rgba(0,0,0,0.13);
	padding: 28px 28px 20px 28px;
	max-width: 370px;
	min-width: 300px;
	text-align: left;
	font-size: 1rem;
	color: #343a40;
	border: 1px solid #e5e7eb;
	opacity: 0;
	pointer-events: none;
	transform: translateY(40px);
	transition: opacity .45s cubic-bezier(.4,0,.2,1), transform .45s cubic-bezier(.4,0,.2,1);
}
#cookieBanner.visible {
	opacity: 1;
	pointer-events: auto;
	transform: translateY(0);
}
.cookie-switch {
	display: flex;
	align-items: center;
	margin-bottom: 10px;
}
.cookie-switch-label {
	position: relative;
	display: inline-block;
	width: 44px;
	height: 24px;
	margin-right: 12px;
}
.cookie-switch-label input[type="checkbox"] {
	opacity: 0;
	width: 44px;
	height: 24px;
	margin: 0;
	position: absolute;
	left: 0; top: 0;
	z-index: 2;
	cursor: pointer;
}
.cookie-switch-label .slider {
	position: absolute;
	cursor: pointer;
	top: 0; left: 0; right: 0; bottom: 0;
	background: #e5e7eb;
	border-radius: 24px;
	transition: .3s;
	z-index: 1;
}
.cookie-switch-label input:checked + .slider {
	background: var(--gradient-primary,#8b5cf6);
}
.cookie-switch-label .slider:before {
	position: absolute;
	content: "";
	height: 18px;
	width: 18px;
	left: 3px;
	bottom: 3px;
	background: #fff;
	border-radius: 50%;
	transition: .3s;
	box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.cookie-switch-label input:checked + .slider:before {
	transform: translateX(20px);
}
.cookie-switch .desc {
	font-size: 0.97em;
	color: #555;
}
.cookie-btn {
	background: var(--gradient-primary,#8b5cf6);
	color: #fff;
	border: none;
	border-radius: 8px;
	padding: 8px 18px;
	font-weight: 500;
	cursor: pointer;
	margin-right: 8px;
	margin-top: 8px;
	transition: background .2s;
}
.cookie-btn:last-child { margin-right: 0; }
.cookie-btn.secondary {
	background: #e5e7eb;
	color: #343a40;
}
</style>
<?php
require_once __DIR__ . '/../includes/settings.php';
$privacy_url = get_setting('privacy_policy_url', '/rental/index.php?page=privacy-policy');
?>
<div id="cookieBanner">
	<form id="cookieConsentForm">
		<div style="margin-bottom:10px;">
			<span>Ta strona korzysta z plików cookies w celach opisanych w <a href="<?= htmlspecialchars($privacy_url) ?>" style="color:#7c3aed; text-decoration:underline;" target="_blank" rel="noopener">Polityce prywatności</a>.</span>
		</div>
			<div class="cookie-switch">
				<span class="cookie-switch-label">
					<input type="checkbox" checked disabled tabindex="-1">
					<span class="slider"></span>
				</span>
				<span class="desc">Wymagane (niezbędne do działania strony)</span>
			</div>
			<div class="cookie-switch">
				<span class="cookie-switch-label">
					<input type="checkbox" id="consent_stats" name="stats">
					<span class="slider"></span>
				</span>
				<span class="desc">Statystyczne (anonimowe statystyki)</span>
			</div>
			<div class="cookie-switch">
				<span class="cookie-switch-label">
					<input type="checkbox" id="consent_marketing" name="marketing">
					<span class="slider"></span>
				</span>
				<span class="desc">Marketingowe (np. newsletter, remarketing)</span>
			</div>
		<div style="margin-top:10px; text-align:right;">
			<button type="button" id="cookieAcceptAllBtn" class="cookie-btn">Akceptuję wszystkie</button>
			<button type="submit" id="cookieSaveBtn" class="cookie-btn secondary">Zapisz wybór</button>
		</div>
	</form>
</div>

</body>

</html>
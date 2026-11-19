</main>

            <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 border-t glass-nav pb-safe"
                 aria-label="Navegación móvil">
                <div class="grid grid-cols-5 max-w-lg mx-auto">
                    <?php foreach ($links as $file => [$label, $icon]):
                        $active = $current === $file;
                    ?>
                    <a href="<?= $file ?>.php"
                       class="flex flex-col items-center justify-center gap-0.5 min-h-14 py-2 text-[10px] font-medium
                              <?= $active ? 'text-accent' : 'muted' ?>">
                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/>
                        </svg>
                        <span><?= $label ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </nav>
        </div>
    </div>
    <script src="assets/js/app.js"></script>
</body>
</html>

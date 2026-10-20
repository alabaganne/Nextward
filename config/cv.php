<?php

// Resume and letter PDFs are printed by headless Chrome through Browsershot (see README.md#resumes).
return [
    // Leave empty to use the first Chrome found (/usr/bin/google-chrome, chromium) or the one puppeteer downloaded.
    'chrome' => env('CV_CHROME_PATH'),
    'node' => env('CV_NODE_BINARY'),
    'npm' => env('CV_NPM_BINARY'),
    // Chrome's sandbox can't start as root or in some containers.
    'no_sandbox' => (bool) env('CV_CHROME_NO_SANDBOX', false),
    'timeout' => (int) env('CV_PDF_TIMEOUT', 60),
    // Tests set this so no browser is started; a small placeholder PDF is returned instead.
    'fake' => (bool) env('CV_PDF_FAKE', false),
];

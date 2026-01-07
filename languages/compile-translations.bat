@echo off
echo ========================================
echo Bulk Price & Discount Editor
echo Translation Compiler
echo ========================================
echo.

REM Check if msgfmt is available
where msgfmt >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: msgfmt not found!
    echo.
    echo You need to install gettext tools.
    echo.
    echo Option 1: Download Poedit from https://poedit.net/
    echo           Open each .po file and save it to generate .mo files
    echo.
    echo Option 2: Install gettext tools for Windows
    echo           https://mlocati.github.io/articles/gettext-iconv-windows.html
    echo.
    pause
    exit /b 1
)

echo Compiling translations...
echo.

REM Compile Persian
if exist bulk-price-discount-editor-for-woocommerce-fa_IR.po (
    echo Compiling Persian ^(fa_IR^)...
    msgfmt -o bulk-price-discount-editor-for-woocommerce-fa_IR.mo bulk-price-discount-editor-for-woocommerce-fa_IR.po
    if %ERRORLEVEL% EQU 0 (
        echo   [OK] Persian compiled successfully
    ) else (
        echo   [ERROR] Failed to compile Persian
    )
) else (
    echo   [SKIP] Persian .po file not found
)

echo.

REM Compile English
if exist bulk-price-discount-editor-for-woocommerce-en_US.po (
    echo Compiling English ^(en_US^)...
    msgfmt -o bulk-price-discount-editor-for-woocommerce-en_US.mo bulk-price-discount-editor-for-woocommerce-en_US.po
    if %ERRORLEVEL% EQU 0 (
        echo   [OK] English compiled successfully
    ) else (
        echo   [ERROR] Failed to compile English
    )
) else (
    echo   [SKIP] English .po file not found
)

echo.
echo ========================================
echo Compilation complete!
echo ========================================
echo.
pause

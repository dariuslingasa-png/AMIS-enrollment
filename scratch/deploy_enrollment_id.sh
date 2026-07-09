#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/enrollment.amis.edu.ph"
ARCHIVE_NAME="enrollment_id_deploy.tar.gz"

echo "=== 1. Bundling modified files in amis_enrollment ==="
tar -czf $ARCHIVE_NAME \
    app/Http/Controllers/IdVerificationController.php \
    resources/views/enrollment/id-verification.blade.php \
    routes/web.php \
    public/build/manifest.json \
    public/build/assets/app-1Qe_ZxdN.css \
    public/build/assets/app-DsIK1Lmc.js

echo "=== 2. Uploading bundle to production server ==="
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "=== 3. Extracting bundle on production and clearing caches ==="
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "
    cd $REMOTE_PATH && \
    tar -xzf $ARCHIVE_NAME && \
    rm $ARCHIVE_NAME && \
    php artisan optimize:clear && \
    php artisan route:clear && \
    php artisan route:cache && \
    php artisan config:clear && \
    php artisan config:cache && \
    php artisan view:cache
"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "=== Enrollment portal changes deployed successfully! ==="

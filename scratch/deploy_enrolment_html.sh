#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/enrollment.amis.edu.ph"
ARCHIVE_NAME="enrolment_html_deploy.tar.gz"

echo "=== 1. Bundling enrolment_form.html in amis_enrollment ==="
tar -czf $ARCHIVE_NAME \
    public/enrolment_form.html \
    public/images/deped_logo.png

echo "=== 2. Uploading bundle to production server via SCP ==="
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "=== 3. Extracting bundle on production ==="
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "
    cd $REMOTE_PATH && \
    tar -xzf $ARCHIVE_NAME && \
    rm $ARCHIVE_NAME
"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "=== enrolment_form.html deployed successfully to enrollment.amis.edu.ph! ==="

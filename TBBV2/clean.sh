#!/bin/bash

# Clean up logs - move to archive dir
for f in `find /var/www/apbb/logs -maxdepth 1 -mtime +8 -name "*_log_*"`; do mv $f /var/www/apbb/logs/archive/; done
# Clean up logs - delete archive over 1month
for f in `find /var/www/apbb/logs/archive -mtime +30 -name "*_log_*"`; do rm $f; done

# Clean up backup - delete over 1month
for f in `find /home/ec2-user/apbb_backup -maxdepth 1 -mtime +30 -name "*tar.gz"`; do rm $f; done

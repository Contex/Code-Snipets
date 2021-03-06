#!/bin/bash

# Created by Contex (http://contex.me) 2012
# This bash shell exports all the databases in the MYSQL_DATABASES variable with gzip compression.
# It then compresses all the databases and uploads it to a remote host SSH_HOST.
# It also sends an e-mail report to EMAIL_TO.
#
# For automated backup, create a crontab entry for this script, example: 
#	crontab -e
#	@daily bash /home/backup/mysql_backup.sh
#	CTRL + X
# 	Y
#	crontab -l
#
# NOTE: CONFIGURATION_IS_SET has to be set before running this script.
# NOTE: Requires a RSA key to be setup for the SSH host, see: http://www.g-loaded.eu/2005/11/10/ssh-with-keys/
# NOTE: This script will not backup to a remote host unless you've setup RSA Keys correctly (without passphrase).
# NOTE: 'mail' is required to send an e-mail report.
# NOTE: MYSQL_USERNAME needs to have permissions to all the databases.
# NOTE: This script has to be run via the 'bash' command and not 'sh'.

# START CONFIGURATION
	# Local files directory configuration
	BACKUP_DIRECTORY="/home/backups"
	BACKUP_INNER_DIRECTORY="mysql"
	BACKUP_NAME="contex"
	BACKUP_PREFIX=`eval date +%Y"-"%m"-"%d`
	
	# MySQL configuration
	MYSQL_USERNAME="contex"
	MYSQL_PASSWORD="password"
	MYSQL_DATABASES=("contex_database1" "contex_database2" "contex_database3" "contex_database4" "contex_database5")
	
	# Remote SSH configuration
	SSH_HOST="contex.me"
	SSH_USERNAME="contex"
	SSH_DIRECTORY="/home/backups/"$BACKUP_INNER_DIRECTORY
	
	# E-mail configuration
	EMAIL_TO="me@contex.me"
	EMAIL_SUBJECT="Contex MySQL Backup"
	
	# Set to true when configuration is finished
	CONFIGURATION_IS_SET=false
# END CONFIGURATION

if ! $CONFIGURATION_IS_SET ; then
	echo "Confgiuration has NOT be set to true, go through the configuration and set the CONFIGURATION_IS_SET variable to true once finished."
	exit
fi

# Start e-mail message
	EMAIL_MESSAGE="/tmp/"$BACKUP_NAME"_email_message.txt"
	echo "This is an automated message generated by the backup script." > $EMAIL_MESSAGE
	echo "" >> $EMAIL_MESSAGE
	echo "A MySQL backup has been generated and uploaded to the remote host." >> $EMAIL_MESSAGE
	echo "" >> $EMAIL_MESSAGE
	echo "Backup log is displayed below:" >> $EMAIL_MESSAGE
	echo "" >> $EMAIL_MESSAGE
# End e-mail message

# Start create directories
	if [ ! -d "$BACKUP_DIRECTORY" ]; then
		mkdir "$BACKUP_DIRECTORY"
	fi
	if [ ! -d "$BACKUP_DIRECTORY/$BACKUP_INNER_DIRECTORY" ]; then
		mkdir "$BACKUP_DIRECTORY/$BACKUP_INNER_DIRECTORY"
	fi
	if [ ! -d "$BACKUP_DIRECTORY/$BACKUP_INNER_DIRECTORY/tmp" ]; then
		mkdir "$BACKUP_DIRECTORY/$BACKUP_INNER_DIRECTORY/tmp"
	fi
# End create directories

# Change directory to $BACKUP_INNER_DIRECTORY temporarily
cd $BACKUP_DIRECTORY/$BACKUP_INNER_DIRECTORY/tmp
START_TOTAL_TIME=`date +%s`

# Start loop through the databases
	for MYSQL_DATABASE in "${MYSQL_DATABASES[@]}"
	do
		START_TIME=`date +%s`

		BACKUP_FILENAME=$BACKUP_PREFIX"_"$MYSQL_DATABASE
		mysqldump -u $MYSQL_USERNAME -p$MYSQL_PASSWORD $MYSQL_DATABASE | gzip -9 > $BACKUP_FILENAME.sql.gz
		
		END_TIME=`date +%s`
		TIME_ELAPSED=$(($END_TIME-$START_TIME))
		echo "Took $TIME_ELAPSED seconds to backup MySQL database: '$MYSQL_DATABASE'." >> $EMAIL_MESSAGE
		FILE_SIZE=$(stat -c%s "$BACKUP_FILENAME.sql.gz")
		echo "Compressed MySQL database '$MYSQL_DATABASE' size: $FILE_SIZE bytes" >> $EMAIL_MESSAGE
	done
# End loop through the databases

# Start compress MySQL backups
	START_TIME=`date +%s`
	BACKUP_FILENAME=$BACKUP_PREFIX"_"$BACKUP_NAME"_"$BACKUP_INNER_DIRECTORY
	tar -zcvf $BACKUP_FILENAME.tar.gz *
	mv $BACKUP_FILENAME.tar.gz $BACKUP_DIRECTORY/$BACKUP_INNER_DIRECTORY/$BACKUP_FILENAME.tar.gz

	for file in $BACKUP_DIRECTORY/$BACKUP_INNER_DIRECTORY/tmp/*
	do
	  echo "Deleted MySQL temporarily backup: $file" >> $EMAIL_MESSAGE
	  rm $file
	done
	END_TIME=`date +%s`
	TIME_ELAPSED=$(($END_TIME-$START_TIME))
	echo "Took $TIME_ELAPSED seconds to compress MySQL backups." >> $EMAIL_MESSAGE
	FILE_SIZE=$(stat -c%s "$BACKUP_DIRECTORY/$BACKUP_INNER_DIRECTORY/$BACKUP_FILENAME.tar.gz")
	echo "Compressed MySQL databases size: $FILE_SIZE bytes" >> $EMAIL_MESSAGE
# End compress MySQL backups

# Start SSH file to other server
	START_TIME=`date +%s`
	ssh -l $SSH_USERNAME $SSH_HOST mkdir -p $SSH_DIRECTORY
	scp $BACKUP_DIRECTORY/$BACKUP_INNER_DIRECTORY/$BACKUP_FILENAME.tar.gz $SSH_USERNAME@$SSH_HOST:$SSH_DIRECTORY
	END_TIME=`date +%s`
	TIME_ELAPSED=$(($END_TIME-$START_TIME))
	echo "Took $TIME_ELAPSED seconds to upload MySQL backup to the remote host." >> $EMAIL_MESSAGE
	echo "Local file: $BACKUP_DIRECTORY/$BACKUP_INNER_DIRECTORY/$BACKUP_FILENAME.tar.gz" >> $EMAIL_MESSAGE
	echo "Remote file: $SSH_DIRECTORY/$BACKUP_FILENAME.tar.gz" >> $EMAIL_MESSAGE
# End SSH file to other server

END_TOTAL_TIME=`date +%s`
TOTAL_TIME_ELAPSED=$(($END_TOTAL_TIME-$START_TOTAL_TIME))
echo "Took a total of $TOTAL_TIME_ELAPSED seconds to run the MySQL backup." >> $EMAIL_MESSAGE
mail -s "$EMAIL_SUBJECT" "$EMAIL_TO" < $EMAIL_MESSAGE
exit
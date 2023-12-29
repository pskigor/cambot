#!/bin/bash
ffmpeg -y -i "rtsp://admin:admin@192.168.0.10:554" -vframes 1 /tgbot/cam/cam1.jpg
chmod ugo+rwx /tgbot/cam/cam1.jpg
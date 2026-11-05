HERO VIDEO
==========

Drop a file named  hero.mp4  in this folder (/assets/video/hero.mp4) and the home-page hero plays it.
No code or CMS change is needed. When this file exists it is used instead of any video set on the
hero section in the CMS; delete it to go back to the CMS video or photo.

Recommended specs
- Format: MP4, H.264 video (High profile), yuv420p
- Size: 1920 px wide (1080p), any landscape ratio (it is cropped to fill with object-fit: cover)
- File size: under ~8 MB
- Length: 10-20 seconds, edited to loop smoothly
- Audio: none (the hero is always muted; strip the audio track to save size)
- Encode with "fast start" so it begins playing before it has fully downloaded

Example ffmpeg command:
  ffmpeg -i input.mov -ss 0 -t 18 -an -vf "scale=1920:-2,fps=30" -c:v libx264 -profile:v high \
         -preset slow -crf 24 -maxrate 3200k -bufsize 6400k -pix_fmt yuv420p -movflags +faststart hero.mp4

How it behaves
- Starts muted, inline, looping. If the browser blocks autoplay or the file cannot be played,
  the poster photo stays on screen.
- Pauses while the tab is hidden or the hero is scrolled off-screen, resumes after.
- Visitors who ask their device for reduced motion see the poster; the round pause/play button in
  the bottom-left of the hero lets anyone start or stop the video.
- Data-saver / 2G connections get the poster only (existing behaviour of main.js).

The current hero.mp4 is an 18-second cut of the supplied property film (letterbox bars removed,
1920x864, no audio, 7.4 MB).

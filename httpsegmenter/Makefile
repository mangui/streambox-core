all:
	gcc -Wall -g segmenter.c -o segmenter -L/usr/local/lib -lavformat -lavcodec -lavutil -lz -lm -laacplus -lx264 -lpthread -lmp3lame -lbz2 -std=c99

clean:
	rm segmenter

install: segmenter
	cp segmenter /usr/local/bin/

uninstall:
	rm /usr/local/bin/segmenter

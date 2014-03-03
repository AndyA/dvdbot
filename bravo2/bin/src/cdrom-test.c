// gcc -o cdrom-test cdrom-test.c

#include <stdio.h>
#include <stdlib.h>
#include <errno.h>
#include <string.h>
#include <unistd.h>
#include <fcntl.h>
#include <sys/ioctl.h>
#include <linux/cdrom.h>


int
main (int argc, char **argv)
{
	char *program;
	char *device;
	int fd;           /* file descriptor for CD-ROM device */
	int status;       /* return status for system calls */
	int verbose = 0;

	program = argv[0];

	++argv;
	--argc;

	if (argc < 1 || argc > 2) {
		fprintf (stderr, "usage: %s [-v] <device>\n",
			 program);
		exit (1);
	}
 
       if (strcmp (argv[0], "-v") == 0) {
                verbose = 1;
                ++argv;
                --argc;
        }
 
	device = argv[0];
 
	/* open device */ 
	fd = open(device, O_RDONLY | O_NONBLOCK);
	if (fd < 0) {
		fprintf (stderr, "%s: open failed for `%s': %s\n",
			 program, device, strerror (errno));
		exit (1);
	}

	/* Check CD player status */ 

	printf ("Drive status: ");
	status = ioctl (fd, CDROM_DRIVE_STATUS, CDSL_CURRENT);
	if (status<0) {
		perror(" CDROM_DRIVE_STATUS");
	} else switch(status) {
	case CDS_DISC_OK:
		printf ("Ready.\n");
		break;
	case CDS_TRAY_OPEN:
		printf ("Tray Open.\n");
		break;
	case CDS_DRIVE_NOT_READY:
		printf ("Drive Not Ready.\n");
		break;
	default:
		printf ("This Should not happen!\n");
		break;
	}


	status = ioctl (fd, CDROM_DRIVE_STATUS, 0);
	if (status<0) {
		perror(" CDROM_DRIVE_STATUS");
	} else switch(status) {
	case CDS_DISC_OK:
		printf ("Disc present.");
		break;
	case CDS_NO_DISC: 
		printf ("Empty slot.");
		break;
	case CDS_TRAY_OPEN:
		printf ("CD-ROM tray open.\n");
		break;
	case CDS_DRIVE_NOT_READY:
		printf ("CD-ROM drive not ready.\n");
		break;
	case CDS_NO_INFO:
		printf ("No Information available.");
		break;
	default:
		printf ("This Should not happen!\n");
		break;
	}

	status = ioctl (fd, CDROM_DISC_STATUS);
	if (status<0) {
		perror(" CDROM_DISC_STATUS");
	}
	switch (status) {
	case CDS_AUDIO:
		printf ("\tAudio disc.\t");
		break;
	case CDS_DATA_1:
	case CDS_DATA_2:
		printf ("\tData disc type %d.\t", status-CDS_DATA_1+1);
		break;
	case CDS_XA_2_1:
	case CDS_XA_2_2:
		printf ("\tXA data disc type %d.\t", status-CDS_XA_2_1+1);
		break;
	default:
		printf ("\tUnknown disc type 0x%x!\t", status);
		break;
	}

	status = ioctl (fd, CDROM_MEDIA_CHANGED, 0);
	if (status<0) {
		perror(" CDROM_MEDIA_CHANGED");
	}
	switch (status) {
	case 1:
		printf ("Changed.\n");
		break;
	default:
		printf ("\n");
		break;
	}

	{
		int foo;
		dvd_struct dvd;

		dvd.type = DVD_STRUCT_COPYRIGHT;
		dvd.copyright.layer_num = 0;

		status = ioctl( fd, DVD_READ_STRUCT, &dvd );

		foo = dvd.copyright.cpst;

		if (status < 0)
		{
			perror ("Not a DVD");
		} else switch (foo) {
		case 0:
			printf ("DVD not encrypted\n");
			break;
		case 1:
			printf ("DVD encrypted\n");
			break;
		default:
			printf ("Shouldn't happen\n");
			break;
		}

		printf ("RMI is: %#02x\n", dvd.copyright.rmi);
	}

	/* close device */
	status = close (fd);
	if (status != 0) {
		fprintf (stderr, "%s: close failed for `%s': %s\n",
			 program, device, strerror (errno));
		exit (1);
	}
 
	exit (0);
}

/* Cdstatus.c: Show the status of the cdrom player. 
   (c) 1997 David A. van Leeuwen

 */
#include <stdio.h>
#include <stdlib.h>
#include <fcntl.h>
#include <linux/cdrom.h>
#include <sys/ioctl.h>

int main(int ac, char ** av) 
{
  char * device = "/dev/sr0";
  int fd, cont=0, c;
  int flags;
  FILE * errorfile = stderr;
  extern int optind, optopt;
/*  while((c=getopt(ac, av, "ch"))!=EOF) {
    switch(c) {
    case 'c': 
      cont=1;
      break;
    case 'h':
      printf("cdstatus. Show the status of the cdrom player.\n"
	     "(c) 1997 David A. van Leeuwen\n\n");
      errorfile=stdout;
    default:
      fprintf(errorfile, "Usage:\n\tcdstatus [-c] [device]\n"
	      "\t-c:\trun contineously\n"
	      "\tdevice:\tdefaults to /dev/cdrom\n");
      exit(-1);
    }
  }*/
  if (ac>optind) device = av[optind];
  fd = open(device, O_RDONLY | O_NONBLOCK);
  if (fd < 0) {
    perror("Can't open device");
    exit(-2);
  }
  printf("Drive options 0x%x\n", ioctl(fd, CDROM_SET_OPTIONS, 0));

//  do {
    int status = ioctl(fd, CDROM_DRIVE_STATUS, 0);
    if (status<0) {
      fprintf(stderr, "ioctl returned %d; ", status);
      perror("ioctl error");
      exit (-3);
    }
    switch(status) {
    case CDS_NO_INFO: 
      printf("No information available\n");
      break;
    case CDS_NO_DISC:
      printf("No disc inserted\n");
      break;
    case CDS_TRAY_OPEN:
      printf("CDROM tray open\n");
      break;
    case  CDS_DRIVE_NOT_READY: 
      printf("Drive not ready\n");
      break;
    case CDS_DISC_OK:
      printf("Disc found;\n");
      status=ioctl(fd,  CDROM_DISC_STATUS);
      if (status < 0) break;
      switch (status) {
      case CDS_AUDIO:
	printf("  Audio disc\n");
	break;
      case CDS_DATA_1:
      case CDS_DATA_2:
	printf("  Data disc type %d\n", status-CDS_DATA_1+1);
	break;
      case CDS_XA_2_1:
      case CDS_XA_2_2:
	printf("  XA data disc type %d\n", status-CDS_XA_2_1+1);
        break;
      default:
	printf("  Unknown disc type\n");  
      }
      break;
    default:
      printf("Unknow ioctl return value %d\n", status);
    }
//    if (cont) sleep(5);
//  } while (cont);
}

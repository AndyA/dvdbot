#include <stdio.h>
#include <stdlib.h>
#include <fcntl.h>

int main(int argc, char **argv)
 {
  int i,fp;
  char c;

//  char b[];

/*
int fd;
char *Path="ftest";
int Flags= O_WRONLY;
char Buff[]="V8 cars are coool";

fd = open(Path, Flags);
if (fd<0)
 {
  printf("Cannot open file ftest\n");
  exit(1);
 }
write(fd, Buff, strlen(Buff)+1);
close(fd);
*/

  if (argc != 2)
   {
    fprintf(stderr, "Bad param, use %s file\n", *argv);
    exit(1);
   }


//  if ((fp = open(argv[1], O_APPEND|O_WRONLY|O_NONBLOCK|O_SYNC)) < 0)
  if ((fp = open(argv[1], O_RDWR)) < 0)
   {
    printf("Cannot open file %s.\n",argv[1]);
    exit(1);
   }

  while ((c = getchar()) != EOF)
   {
//    printf("%c %i",c,c);
    write(fp, &c, 1);
//    b[0]=c;
//    write(fp, Buff, strlen(Buff)+1);
//    write(fp, b, 1);
    }
/*  printf("\n");

  for (i=0; i<89; i++)
   {
    read(fp,&c,1);
    printf("%x",c);
   }
  printf("\n");

  for (i=0; i<89; i++)
   {
    read(fp,&c,1);
    printf("%x",c);
   }

*/
//write(fp, Buff, strlen(Buff)+1);

  read(fp,&c,1);

  if( close( fp ))
   {
    printf("File close error.\n");
   }

  return 0;
 }

FROM ubuntu:14.04

RUN apt-get update

RUN apt-get install -y openssh-server
RUN mkdir /var/run/sshd

RUN echo 'root:root' |chpasswd

RUN sed -ri 's/^PermitRootLogin\s+.*/PermitRootLogin yes/' /etc/ssh/sshd_config
RUN sed -ri 's/UsePAM yes/#UsePAM yes/g' /etc/ssh/sshd_config

RUN mkdir ~/.ssh
RUN echo "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC2tGpPt3qrI/G4QOJeP7kA2ihC9U/cY42GNZuedrKocNGGQBgvjGNRP1kCexXeaJ25KUti+tFbPjVrdt6xhqALiwUupSYFusaAVEDcwl6KLMGAOHWD0X7hdraL3w4ATyNTBvowyc9ZvsollNyTUqDWxN2USTfk89Xd+S2noAfoCUJd4fMEmBeAAqlYTajHGKSKYcRGokxci7dyaXoHEQgsd0giZWIZATNUkWTV36bTnGjMxz4pK6LHNglQRNO4GpxQI5Bmie60gVcOIQAhmbrTq9bwcoLq6SGBOUO/Vr6ZnLcI8h4W50HSNUeXKtFMeNMgzDgS8x4Ow0XLEpjujRGh miroslavcillik@Miroslavs-MacBook-Air.local" >> ~/.ssh/authorized_keys

EXPOSE 22

CMD    ["/usr/sbin/sshd", "-D"]


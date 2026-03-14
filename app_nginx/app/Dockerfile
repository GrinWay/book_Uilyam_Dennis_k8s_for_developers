# For local_nginx:1.28-alpine-perl
FROM nginx:1.28-alpine-perl

RUN chgrp 65534 -R /var/cache/nginx /run /var/run /var/log/nginx && \
    chmod g+rwX -R /var/cache/nginx /run /var/run /var/log/nginx
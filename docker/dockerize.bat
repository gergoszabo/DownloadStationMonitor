docker container prune -f
docker image prune -f
docker build -t dsm .
docker image prune -f
docker run -it -p 80:80 dsm:latest
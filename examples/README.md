# PHP Examples

This directory contains a few examples using the SST SDK.

## Running

The example scripts share a web server, and depend on a fake local CDN for the
SST config. To start the local web server and CDN, run:

```
./example-server.sh
```

Edit the script if you need to use other ports than we selected for the
examples.

## Testing

Visit http://localhost:8910/Hello.php to see a simple page showing the selected
variation. Clear your cookies on localhost (or just the one called sg_cookies)
to get a new allocation.

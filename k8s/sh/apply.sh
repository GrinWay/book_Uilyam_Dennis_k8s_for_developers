#! /bin/bash

set -ex

# Force set correct namespace like: dev, test, prod
kubectl config set-context --current --namespace app

# Execute all the rest...
$@
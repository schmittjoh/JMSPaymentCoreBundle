# CONTRIBUTING

## Documentation
### Setup

    brew install graphviz

    sudo pip install virtualenv
    virtualenv venv
    source venv/bin/activate
    pip install -r requirements.txt

### Building the docs
    sphinx-build -b html Resources/doc Resources/doc/_build
    sphinx-autobuild -B Resources/doc Resources/doc/_build

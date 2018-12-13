# CONTRIBUTING

## Tests
Before submitting a PR, make sure tests pass:

    bin/composer install
    bin/phpunit

## Documentation
Documentation is built using [Sphinx](http://www.sphinx-doc.org).

### Setup
Make sure you have [pip installed](https://pip.pypa.io/en/stable/installing/) and then install the needed dependencies
inside a [virtualenv](https://virtualenv.pypa.io) environment, in order not to pollute your system:

    sudo pip install virtualenv

You also need to install [Graphviz](http://www.graphviz.org), which is used for generating graphs. It is mostly likely 
available through your package manager:

    [macOS] brew install graphviz
    [debian] sudo apt-get instal graphviz

### Building the docs
Build the documentation with:

    make docs

Alternatively, use `sphinx-autobuild` to watch changes and automatically refresh the browser:

    venv/bin/sphinx-autobuild -E -B Resources/doc Resources/doc/_build

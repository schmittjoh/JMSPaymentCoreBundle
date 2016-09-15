#
# Sphinx configuration file
#
# See CONTRIBUTING.md for instructions on how to build the documentation.
#

import sphinx_rtd_theme

project = u'JMSPaymentCoreBundle'

extensions = [
    'sensio.sphinx.configurationblock',
    'sphinx.ext.graphviz',
]

master_doc = 'index'

html_show_copyright = False
html_theme = 'sphinx_rtd_theme'
html_theme_path = [sphinx_rtd_theme.get_html_theme_path()]

graphviz_output_format = 'png'
graphviz_dot_args = [
    '-Gbgcolor=transparent',
    '-Grankdir=LR',
]

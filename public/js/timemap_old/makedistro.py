# makedistro.py
# build script for packing the TimeMap library with the YUI Compressor
#
# @author Nick Rabinowitz (www.nickrabinowitz.com)

import sys, os
import shutil, glob

# default paths - mostly for my convenience
#'yuic': r'"C:\Program Files\yuicompressor\build\yuicompressor.jar"',
if sys.platform.startswith('linux'):
    yuic = r'/home/nick/tools/yuicompressor-2.4.2/build/yuicompressor-2.4.2.jar'
    jsdoc = r'/home/nick/tools/jsdoc-toolkit'
else:
    yuic = r'"C:\Program Files\yuicompressor\build\yuicompressor.jar"'
    jsdoc = r'"C:\Program Files\jsdoc-toolkit"'
runonly = False
verbose = ""

# get paths and runonly from args
if len(sys.argv) > 1:
    for arg in sys.argv:
        if arg.startswith("--yuic="):
            yuic = arg[7:]
        elif arg.startswith("--jsdoc="):
            jsdoc = arg[8:]
        elif arg.startswith("--runonly"):
            runonly = arg[10:]
        elif arg == "--verbose" or arg == "-v":
            verbose = "-v"

# make packed distro files
if not runonly or runonly == 'yuic':
    # pack and copy core lib
    os.system("java -jar %s %s timemap.js > timemap.pack.js" % (yuic, verbose))
    print "Packed timemap.js"
    shutil.copy("timemap.pack.js", "timemap_full.pack.js")

    # make list of files to pack
    ignore = ['timemap.js', 'timemap.pack.js', 'timemap_full.pack.js']
    files = [f for f in glob.glob('*.js') if not f in ignore]
    # prepend libraries
    files = [os.path.join('lib', 'json2.pack.js')] + files
    # append loaders
    files += [f for f in glob.glob(os.path.join('loaders','*.js'))]

    # pack and add files
    for f in files:
        os.system("java -jar %s %s %s >> timemap_full.pack.js" % (yuic, verbose, f))
        print "Packed and added %s" % f

# make documentation
if not runonly or runonly == 'jsdoc':

    # make a list of files to parse for docs
    ignore = ['timemap.pack.js', 'timemap_full.pack.js']
    files = [f for f in glob.glob('*.js') if not f in ignore]
    # append loaders
    files += [f for f in glob.glob(os.path.join('loaders','*.js'))]
    
    # run jsdoc to create docs
    os.system("java -Djsdoc.dir=%s -jar %s %s -t=%s %s -d=docs -r=1 %s" % (
        jsdoc, os.path.join(jsdoc, 'jsrun.jar'), os.path.join(jsdoc, 'app', 'run.js'),
        os.path.join(jsdoc, 'templates', 'jsdoc'), verbose, " ".join(files)
    ))
    print "Created documentation in docs/ directory"

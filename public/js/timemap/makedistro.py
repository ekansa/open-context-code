help = """
Distribution build script: runs YUI Compressor and jsdoc-toolkit.

Arguments:

    --yuic          Path to YUI Compressor .jar file
    --jsdoc         Path to jsdoc-toolkit directory
    --runonly       Only run specified program; options are "yuic" or "jsdoc"
    --verbose, -v   Output verbose messages
    --help, -h      Print this message
"""
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
        elif arg == "--help" or arg == "-h":
            print help
            exit()

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

# make packed distro files, wrapping everything in an anonymous function
if runonly == 'yuictest':
    # set up template
    codehead = "(function(){\n"
    codefoot = "\n})();"
    
    # create and pack stand-alone core lib
    tmp = open('tmp.js', 'w')
    tmp.write(codehead)
    js = open('timemap.js')
    tmp.write(js.read())
    js.close()
    print "Added timemap.js"
    tmp.write(codefoot)
    tmp.close()
    # pack core lib
    os.system("java -jar %s %s tmp.js > timemap.pack.js" % (yuic, verbose))
    print "Packed timemap.pack.js"
    os.remove('tmp.js')
    
    # create and pack full lib
    tmp = open('tmp.js', 'w')
    
    tmp.write(codehead)
    # make list of files to pack
    ignore = ['timemap.js', 'timemap.pack.js', 'timemap_full.pack.js', 'tmp.js']
    files = ['timemap.js'] + [f for f in glob.glob('*.js') if not f in ignore]
    
    # append loaders
    files += [f for f in glob.glob(os.path.join('loaders','*.js'))]

    # add files
    for f in files:
        js = open(f)
        tmp.write(js.read() + "\n")
        js.close()
        print "Added %s" % f
    
    # close tmp file
    tmp.write(codefoot)
    tmp.close()
        
    # prepend libraries - might need more work here to prepend multiple
    shutil.copy(os.path.join('lib', 'json2.pack.js'), "timemap_full.pack.js")
    
    # pack full lib
    os.system("java -jar %s %s tmp.js >> timemap_full.pack.js" % (yuic, verbose))
    print "Packed timemap_full.pack.js"
    os.remove('tmp.js')
    

# make documentation
if not runonly or runonly == 'jsdoc':

    # make a list of files to parse for docs
    ignore = ['timemap.js', 'timemap.pack.js', 'timemap_full.pack.js']
    files = ['timemap.js', 'README.txt'] + [f for f in glob.glob('*.js') if not f in ignore]
    # append loaders
    files += [f for f in glob.glob(os.path.join('loaders','*.js'))]
    
    # run jsdoc to create docs
    os.system("java -Djsdoc.dir=%s -jar %s %s -c=%s %s -r=1 %s" % (
        jsdoc, os.path.join(jsdoc, 'jsrun.jar'), os.path.join(jsdoc, 'app', 'run.js'),
        os.path.join('docs', 'jsdoc-toolkit', 'timemap.conf'), verbose, " ".join(files)
    ))
    print "Created documentation in docs/ directory"

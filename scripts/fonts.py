#!/usr/local/munkireport/munkireport-python3

"""
Fonts information generator for munkireport.
"""

import subprocess
import os
import plistlib
import sys

# Import ctypes before other framework imports to avoid conflicts
try:
    from ctypes import (CDLL,
                        Structure,
                        POINTER,
                        c_int64,
                        c_int32,
                        c_int16,
                        c_char,
                        c_uint32)
    from ctypes.util import find_library
except (AttributeError, ImportError) as e:
    # Python 3.12 ctypes compatibility issue - ctypes module is broken
    # This indicates a corrupted or incomplete Python 3.12 installation
    # The ctypes module itself fails to load, not our code
    error_msg = str(e) if e else "Unknown error"
    print(f"Error importing ctypes: {error_msg}", file=sys.stderr)
    print("This indicates a broken Python 3.12 installation. Please reinstall Python 3.12.", file=sys.stderr)
    sys.exit(1)

from SystemConfiguration import SCDynamicStoreCopyConsoleUser

class timeval(Structure):
    _fields_ = [
                ("tv_sec",  c_int64),
                ("tv_usec", c_int32),
               ]

class utmpx(Structure):
    _fields_ = [
                ("ut_user", c_char*256),
                ("ut_id",   c_char*4),
                ("ut_line", c_char*32),
                ("ut_pid",  c_int32),
                ("ut_type", c_int16),
                ("ut_tv",   timeval),
                ("ut_host", c_char*256),
                ("ut_pad",  c_uint32*16),
               ]

def is_yes(value):
    """Normalize yes/no values returned by system_profiler."""
    if isinstance(value, bool):
        return value
    if isinstance(value, (int, float)):
        return value == 1
    return str(value).strip().lower() == 'yes'

def get_fonts():
    '''Uses system profiler to get fonts for this machine.'''

    username=current_user()
    
    # Decode if username is bytes
    if isinstance(username, bytes):
        username = username.decode("utf-8", errors="ignore")
    
    # If no user is logged in, return None to prevent data loss
    if not username or username == "":
        return None

    cmd = ['/bin/launchctl', 'asuser', get_uid(username), '/usr/sbin/system_profiler', 'SPFontsDataType', '-xml']
    proc = subprocess.Popen(cmd, shell=False, bufsize=-1,
                                stdin=subprocess.PIPE,
                                stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    output, err = proc.communicate()
    if proc.returncode != 0 or not output:
        err_out = err.decode("utf-8", errors="ignore").strip()
        print(f"fonts.py: failed to collect font data via launchctl asuser (exit={proc.returncode}) {err_out}", file=sys.stderr)
        return None

    try:
        plist = plistlib.loads(output)
        # system_profiler xml is an array
        sp_dict = plist[0]
        items = sp_dict['_items']
        return items
    except Exception:
        print("fonts.py: failed to parse system_profiler output", file=sys.stderr)
        return None

def flatten_get_fonts(array):
    '''Un-nest fonts, return array with objects with relevant keys'''
    out = []
    for obj in array:
        device = {'name': '', 'enabled': 0, 'type_enabled': 0, 'copy_protected': 0, 'duplicate': 0, 'embeddable': 0, 'outline': 0, 'valid': 0}

        # Exclude system fonts and keep non-system fonts
        if 'path' in obj and "/System/Library/" in obj['path']:
            continue

        for item in obj:
            if item == '_items':
                out = out + flatten_get_fonts(obj['_items'])
            elif item == '_name':
                device['name'] = obj[item]
            elif item == 'path':
                device['path'] = obj[item]
            elif item == 'type':
                device['type'] = obj[item]
            elif item == 'enabled' and is_yes(obj[item]):
                device['enabled'] = 1

            # Process each typeface within font
            elif item == 'typefaces':
                for font in obj['typefaces']:
                    for fontitem in font:
                        if fontitem == '_name':
                            device['type_name'] = font[fontitem]
                        elif fontitem == 'family':
                            device['family'] = font[fontitem]
                        elif fontitem == 'fullname':
                            device['fullname'] = font[fontitem]
                        elif fontitem == 'style':
                            device['style'] = font[fontitem]
                        elif fontitem == 'unique':
                            device['unique_id'] = font[fontitem]
                        elif fontitem == 'version':
                            device['version'] = font[fontitem]
                        elif fontitem == 'vendor':
                            device['vendor'] = font[fontitem]
                        elif fontitem == 'trademark':
                            device['trademark'] = font[fontitem]
                        elif fontitem == 'copyright':
                            device['copyright'] = font[fontitem]
                        elif fontitem == 'description':
                            device['description'] = font[fontitem]
                        elif fontitem == 'designer':
                            device['designer'] = font[fontitem]
                        elif fontitem == 'copy_protected' and is_yes(font[fontitem]):
                            device['copy_protected'] = 1
                        elif fontitem == 'duplicate' and is_yes(font[fontitem]):
                            device['duplicate'] = 1
                        elif fontitem == 'embeddable' and is_yes(font[fontitem]):
                            device['embeddable'] = 1
                        elif fontitem == 'enabled' and is_yes(font[fontitem]):
                            device['type_enabled'] = 1
                        elif fontitem == 'outline' and is_yes(font[fontitem]):
                            device['outline'] = 1
                        elif fontitem == 'valid' and is_yes(font[fontitem]):
                            device['valid'] = 1

        if device.get('name') or device.get('type_name') or device.get('path'):
            out.append(device)
    return out

def current_user():

    # local constants
    c = CDLL(find_library("System"))
    username = (SCDynamicStoreCopyConsoleUser(None, None, None) or [None])[0]
    username = [username,""][username in ["loginwindow", None, ""]]

    # If we can't get the current user, get last console login
    if username == "":
        setutxent_wtmp = c.setutxent_wtmp
        setutxent_wtmp.restype = None
        getutxent_wtmp = c.getutxent_wtmp
        getutxent_wtmp.restype = POINTER(utmpx)
        endutxent_wtmp = c.setutxent_wtmp
        endutxent_wtmp.restype = None
        # initialize
        setutxent_wtmp(0)
        entry = getutxent_wtmp()
        while entry:
            e = entry.contents
            entry = getutxent_wtmp()
            if (e.ut_type == 7 and e.ut_line == b"console" and e.ut_user != "root" and e.ut_user != "" and e.ut_user != b"root" and e.ut_user != b""):
                endutxent_wtmp()
                return e.ut_user
    else:
        return username

def get_uid(username):

    # Decode if username is bytes
    if isinstance(username, bytes):
        username = username.decode("utf-8", errors="ignore")

    cmd = ['/usr/bin/id', '-u', username]
    proc = subprocess.Popen(cmd, shell=False, bufsize=-1,
                            stdin=subprocess.PIPE,
                            stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    (output, unused_error) = proc.communicate()
    return output.decode("utf-8", errors="ignore").strip()

def main():
    """Main"""

    # Get results
    fonts_data = get_fonts()
    
    # If data cannot be collected, exit early to prevent deleting existing data
    if fonts_data is None:
        sys.exit(0)
    
    result = flatten_get_fonts(fonts_data)

    # Write font results to cache

    cachedir = '%s/cache' % os.path.dirname(os.path.realpath(__file__))
    output_plist = os.path.join(cachedir, 'fonts.plist')
    with open(output_plist, 'wb') as fp:
        plistlib.dump(result, fp, fmt=plistlib.FMT_XML)

if __name__ == "__main__":
    main()

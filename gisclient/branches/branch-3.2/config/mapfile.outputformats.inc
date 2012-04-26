OUTPUTFORMAT
  NAME "aggpng24"
  DRIVER AGG/PNG
  MIMETYPE "image/png"
  IMAGEMODE RGB
  EXTENSION "png"
  TRANSPARENT ON
END
OUTPUTFORMAT
  NAME gif
  DRIVER "GD/GIF"
  MIMETYPE "image/gif"
  IMAGEMODE PC256
  EXTENSION "gif"
  TRANSPARENT ON
END
OUTPUTFORMAT
  NAME png
  DRIVER "GD/PNG"
  MIMETYPE "image/png"
  IMAGEMODE PC256
  EXTENSION "png"
  TRANSPARENT ON
END
OUTPUTFORMAT
  NAME jpeg
  DRIVER "GD/JPEG"
  MIMETYPE "image/jpeg"
  IMAGEMODE RGB
  EXTENSION "jpg"
  TRANSPARENT ON
END
OUTPUTFORMAT
  NAME wbmp
  DRIVER "GD/WBMP"
  MIMETYPE "image/wbmp"
  IMAGEMODE PC256
  EXTENSION "wbmp"
  TRANSPARENT ON
END
OUTPUTFORMAT
  NAME GTiff
  DRIVER "GDAL/GTiff"
  MIMETYPE "image/tiff"
  IMAGEMODE RGB
  EXTENSION "tif"
  TRANSPARENT ON
END
OUTPUTFORMAT
    NAME 'AGG_Q'
    DRIVER AGG/PNG
    IMAGEMODE RGB
    FORMATOPTION "QUANTIZE_FORCE=ON"
    FORMATOPTION "QUANTIZE_DITHER=OFF"
    FORMATOPTION "QUANTIZE_COLORS=256"
	TRANSPARENT ON
END
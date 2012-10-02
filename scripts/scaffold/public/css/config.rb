http_path = "/"

sass_dir = "sass"
images_dir = "compiled/img"

css_dir = (environment == :production) ? "compiled" : "compiled_debug"
output_style = (environment == :production) ? :compressed : :expanded
sass_options = (environment == :production) ? {:debug_info => false} : {:debug_info => true}
line_comments = (environment == :production) ? false : true
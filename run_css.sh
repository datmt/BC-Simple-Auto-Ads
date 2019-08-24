#!/bin/bash

current_path=$(pwd)
front_scss="/bundle/css/bc-uatc-front.scss"
front_css="/bundle/css/bc-uatc-front.css"
back_scss="/bundle/css/backend.scss"
back_css="/bundle/css/backend.css"
compile_front="$current_path$front_scss:$current_path$front_css"
compile_back="$current_path$back_scss:$current_path$back_css"
sass "$compile_front"
sass "$compile_back"


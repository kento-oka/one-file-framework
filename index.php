<?=(function(){
    try{
        ob_start();

        return json_encode(
            array_map(
                $output = function($v) use (&$output){
                    if(is_resource($v)){
                        return null;
                    }

                    if(is_callable($v)){
                        return $output($v());
                    }

                    if(is_object($v)){
                        return $output(get_object_vars($v));
                    }

                    if(is_array($v)){
                        return array_map($output, $v);
                    }

                    return $v;
                },
                (new class(__DIR__ . "/config.php"){
                    private $config;
                    public function __construct(string $config){
                        $this->config   = include $config;
                    }
                    public function __invoke(string $method, string $path, array $request){
                        $routes = [];

                        foreach($this->config["routes"][$method] ?? [] as $route => $conf){
                            $segments   = explode(
                                "/",
                                "/" === substr($route, 0, 1) ? substr($route, 1) : $route
                            );

                            $node   = &$routes;
                            $deps   = count($segments);
                            $count  = 1;
                            foreach($segments as $segment){
                                if(!isset($node[$segment])){
                                    $node[$segment] = [
                                        "children"  => [],
                                    ];
                                }

                                if($deps === $count++){
                                    $node[$segment]["conf"] = $conf;
                                    break;
                                }

                                $node   = &$node[$segment]["children"];
                            }
                        }

                        $routing    = function($node, $segments) use (&$routing){
                            $segment    = array_shift($segments);
                            $last       = empty($segments);

                            foreach($node as $match => $data){
                                $result = null;

                                if(":" === substr($match, 0, 1)){
                                    if($last){
                                        if(isset($data["conf"])){
                                            $result = $data["conf"] + [
                                                "params" => [
                                                    substr($match, 1) => $segment,
                                                ]
                                            ];
                                        }
                                    }else{
                                        $result = $routing($data["children"], $segments);

                                        if(null !== $result){
                                            $result["params"][substr($match, 1)]    = $segment;
                                        }
                                    }
                                }elseif($match === $segment){
                                    if($last){
                                        if(isset($data["conf"])){
                                            $result = $data["conf"] + ["params" => []];
                                        }
                                    }else{
                                        $result = $routing($data["children"], $segments);
                                    }
                                }

                                if(null !== $result){
                                    return $result;
                                }
                            }

                            return null;
                        };

                        $result = $routing(
                            $routes,
                            explode(
                                "/",
                                "/" === substr($path, 0, 1) ? substr($path, 1) : $path
                            )
                        );

                        if(null === $result){
                            throw new Exception("", 404);
                        }

                        return $this->invokeAction($result["action"], $result["params"]);
                    }
                    private function invokeAction(callable $action, array $params){
                        $refParams  = null;

                        if(is_string($action) && false !== strpos($action, "::")){
                            $action = explode("::", $action, 2);
                        }

                        if(is_object($action)){
                            $refParams  = (new ReflectionMethod($action, "__invoke"))
                                ->getParameters()
                            ;
                        }elseif(is_array($action)){
                            $refParams  = (new ReflectionMethod($action[0], $action[1]))
                                ->getParameters()
                            ;
                        }else{
                            $refParams  = (new ReflectionFunction($action))
                                ->getParameters()
                            ;
                        }

                        $args   = [];

                        foreach($refParams as $refParam){
                            $value  = $params[$refParam->getName()] ?? null;

                            if(null === $value){
                                if($refParam->isDefaultValueAvailable()){
                                    $value  = $refParam->getDefaultValue();
                                }elseif(!$refParam->allowsNull()){
                                    throw new Exception("", 404);
                                }
                            }elseif(null !== ($type = $refParam->getType())){
                                switch($type->getName()){
                                    case "string":
                                        break;
                                    case "int":
                                        if(false !== ($value = filter_var($value, FILTER_VALIDATE_INT))){
                                            break;
                                        }
                                    default:
                                        throw new Exception("", 404);
                                }
                            }

                            $args[] = $value;
                        }

                        return call_user_func_array($action, $args);
                    }
                })(
                    $_SERVER["REQUEST_METHOD"] ?? "GET",
                    (function(){
                        $path   = explode("?", $_SERVER["REQUEST_URI"])[0];

                        return "/" === substr($path, 0, 1) ? substr($path, 1) : $path;
                    })(),
                    (function(){
                        if(isset($_SERVER["CONTENT_TYPE"])){
                            if("application/json" !== trim(strtolower(explode(";", $_SERVER["CONTENT_TYPE"] ?? "")[0]))){
                                throw new Exception("", 400);
                            }
    
                            $json = json_decode(file_get_contents('php://input'), true);
    
                            if(JSON_ERROR_NONE !== json_last_error()){
                                throw new Exception("", 400);
                            }
    
                            return $json;
                        }
                        
                        return [];
                    })()
                )
            ),
            JSON_PRETTY_PRINT
        );
    }catch(\Throwable $e){
        if(400 <= $e->getCode() && 600 > $e->getCode()){
            http_response_code($e->getCode());
        }else{
            http_response_code(500);
        }

        return "";
    }finally{
        ob_end_clean();
        header("Content-type: application/json; charset=utf-8");
    }
})()
?>
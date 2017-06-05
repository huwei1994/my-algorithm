<?php
/*
这个 算法是将 省-市-区数据表 更改为  左右值树层级关系的表
*/
public function  pp()
    {
        $datas = DB::table('d_17')->get();
        //dump($data);
        $flag_father = 0;
        $flag_grand_father = 0;
        foreach($datas as $k=>$data)
        {
            //判断
            if($data->level !== 'hh')
            {
                //如果本次循环的级别是  省级（确定省级的左节点）
                if($data->level == 'province' && $data->district_id == 2)
                {
                    $flag_grand_father = $data->district_id;
                    //1：如果是第一个省的话
                    $left_node = 2;
                    $data->node_left_hander = $left_node;
                    $data->node_level = 2;
                    $up_data = array(
                        'node_left_hander'=>$left_node,
                        'node_level'=>2
                    );
                    DB::table('d_17')->where('district_id',$data->district_id)->update($up_data);
                }

                if($data->level == 'province' && $data->district_id !== 2)
                {
                    $flag_grand_father = $data->district_id;
                    //1：如果不是第一个省的话(省左节点 = 上一个节点右值(若是市) + 2)
                    //1：如果不是第一个省的话(省左节点 = 上一个节点右值(若是区) + 3)
                    $flow_node_array = DB::table('d_17')->select('level','node_level','node_left_hander','node_right_hander')->where('district_id',$data->district_id-1)->get();
                    $flow_node = $flow_node_array[0]->node_right_hander;
                    $flow_left_node = $flow_node_array[0]->node_left_hander;
                    $flow_level = $flow_node_array[0]->level;
                    $flow_node_level = $flow_node_array[0]->node_level;
                    $next_level_array = DB::table('d_17')->select('level','node_left_hander')->where('district_id',$data->district_id+1)->get();
                    $next_level = $next_level_array[0]->level;
                    if(($flow_level == 'district' || $flow_level == 'biz_area') && $flow_node_level == 4)
                    {
                        //如果上一级节点是区
                        $left_node = $flow_node + 3;
                        $data->node_left_hander = $left_node;
                    }
                    if($flow_node_level == 3 && ($flow_level == 'district' || $flow_level == 'city'))
                    {
                        //如果上一级节点层级是3 （可能是市，区）
                        $left_node = $flow_node + 2;
                        $data->node_left_hander = $left_node;
                    }
                    $data->node_level = 2;
                    $up_data = array(
                        'node_left_hander'=>$left_node,
                        'node_level'=>2
                    );
                    DB::table('d_17')->where('district_id',$data->district_id)->update($up_data);

                    if(($flow_level == 'city' || $flow_level == 'district' || $flow_level == 'biz_area')
                        && $next_level == 'province')
                    {
                        //如果省上面是市或者地区 并且下面直接是省
                        //更新本省右节点
                        $right_node = $left_node + 1;
                        $up_data = array(
                            'node_left_hander'=>$left_node,
                            'node_right_hander'=>$right_node,
                            'node_level'=>2
                        );
                        DB::table('d_17')->where('district_id',$data->district_id)->update($up_data);
                    }
                    if($flow_level == 'province' && $flag_grand_father !== 0)
                    {
                        //如果省上面是省的话，下面也是省的话（更新本省节点左右值）
                        //本省左节点 等于上一个节点右值 + 1；
                        $left_node = $flow_left_node + 2;
                        $right_node = $left_node + 1;
                        //本省右节点 等于左节点 + 1；
                        $up_data = array(
                            'node_left_hander'=>$left_node,
                            'node_right_hander'=>$right_node,
                            'node_level'=>2
                        );
                        DB::table('d_17')->where('district_id',$data->district_id)->update($up_data);

                        $grand_father_node = $flow_left_node + 1;//$flag_grand_father
                        DB::table('d_17')->where('district_id',$flag_grand_father)->update(['node_right_hander'=>$grand_father_node]);
                    }
                }

                //如果本次循环的级别是  市级（确定市级的左节点）
                if($data->level == 'city')
                {
                    $flag_father = $data->district_id;
                    //此次循环记录的上一条记录的level值（判断当前市，是否是省下面的第一个市）
                    $is_first_city = DB::table('d_17')->select('level','node_left_hander','node_right_hander')->where('district_id',$data->district_id-1)->get();
                    $next_level_array = DB::table('d_17')->select('level','node_left_hander')->where('district_id',$data->district_id+1)->get();
                    $next_level = $next_level_array[0]->level;
                    if($is_first_city[0]->level == 'province')
                    {
                        //1：如果是该市 是省下面第一个市的话 （该市节点左值 = 省左节点值 + 1）
                        $left_node = $is_first_city[0]->node_left_hander + 1;
                        $data->node_left_hander = $left_node;
                        $data->node_level = 3;
                        $up_data = array(
                            'node_left_hander'=>$left_node,
                            'node_level'=>3
                        );
                        DB::table('d_17')->where('district_id',$data->district_id)->update($up_data);
                    }
                    else
                    {
                        if($is_first_city[0]->level == 'city')
                        {
                            //1：如果是该市 不是省下面第一个市的话（该市节点左值 = 上一个节点右节点值（上一个节点是市） + 1）
                            $left_node = $is_first_city[0]->node_right_hander + 1;
                            $data->node_left_hander = $left_node;
                            $data->node_level = 3;
                            $up_data = array(
                                'node_left_hander'=>$left_node,
                                'node_level'=>3
                            );
                            DB::table('d_17')->where('district_id',$data->district_id)->update($up_data);
                        }
                        if($next_level == 'city')
                        {
                            //如果该城市 下一个记录是市级的话
                            //更新自己的 右节点（自己的左节点 + 1）
                            $right_node = $is_first_city[0]->node_right_hander + 2;
                            $up_data = array(
                                'node_left_hander'=>$right_node - 1,
                                'node_right_hander'=>$right_node,
                                'node_level'=>3
                            );
                            DB::table('d_17')->where('district_id',$data->district_id)->update($up_data);
                        }

                        if($is_first_city[0]->level == 'district' || $is_first_city[0]->level == 'biz_area')
                        {
                            //1：如果是该市 不是省下面第一个市的话（该市节点左值 = 上一个节点右节点值（上一个节点是区） + 2）
                            $left_node = $is_first_city[0]->node_right_hander + 2;
                            $data->node_left_hander = $left_node;
                            $data->node_level = 3;
                            $up_data = array(
                                'node_left_hander'=>$left_node,
                                'node_level'=>3
                            );
                            DB::table('d_17')->where('district_id',$data->district_id)->update($up_data);
                        }

                        if(($is_first_city[0]->level == 'district' || $is_first_city[0]->level == 'biz_area')
                            && $next_level == 'city')
                        {
                            //1：如果是该市 不是省下面第一个市的话（该市节点左值 = 上一个节点右节点值（上一个节点是区） + 2）
                            $left_node = $is_first_city[0]->node_right_hander + 2;
                            $right_node = $left_node + 1;
                            $data->node_left_hander = $left_node;
                            $data->node_level = 3;
                            $up_data = array(
                                'node_left_hander'=>$left_node,
                                'node_right_hander'=>$right_node,
                                'node_level'=>3
                            );
                            DB::table('d_17')->where('district_id',$data->district_id)->update($up_data);
                        }

                    }


                    if($next_level == 'province' && $flag_grand_father !==0)
                    {
                        //如果该城市 下一个记录是省级的话
                        //更新自己的 右节点（自己的左节点 + 1）
                        $left_node = $is_first_city[0]->node_right_hander + 1;
                        $right_node = $left_node + 1;
                        $up_data = array(
                            'node_left_hander'=>$left_node,
                            'node_right_hander'=>$right_node,
                            'node_level'=>3
                        );
                        DB::table('d_17')->where('district_id',$data->district_id)->update($up_data);

                        //更新省级的右节点（省级右节点=本市右节点 + 1）
                        $grand_father_node = $right_node + 1;//$flag_grand_father
                        DB::table('d_17')->where('district_id',$flag_grand_father)->update(['node_right_hander'=>$grand_father_node]);
                    }

                }
                //如果本次循环的级别是  区级（确定区级的左右节点，市级，省级和根节点右节点值）
                if($data->level == 'district' || $data->level == 'biz_area')
                {
                    $is_first_district = DB::table('d_17')->select('level', 'node_level', 'node_left_hander', 'node_right_hander')->where('district_id', $data->district_id - 1)->get();
                    $flow_level = $is_first_district[0]->level;
                    $flow_node_level = $is_first_district[0]->node_level;
                    $next_level_array = DB::table('d_17')->select('level', 'node_left_hander')->where('district_id', $data->district_id + 1)->get();
                    if($data->district_id !== 3271)
                    {
                        $next_level = $next_level_array[0]->level;
                    }

                    if (($flow_node_level == 3 && $flow_level == 'city')
                        || ($flow_node_level == 2 && $flow_level == 'province'))
                    {
                        //如果该区 是第一区的话（该区节点左值 = 市左节点值 + 1）
                        //更新本节点左值，右值
                        $left_node = $is_first_district[0]->node_left_hander + 1;
                        $data->node_left_hander = $left_node;
                        //区右节点 = 区左节点 + 1
                        $right_node = $left_node + 1;
                        $data->node_right_hander = $right_node;
                        if ($flow_node_level == 3)
                        {
                            $node_level = 4;
                        }
                        if ($flow_node_level == 2)
                        {
                            $node_level = 3;
                        }
                        $up_data = array(
                            'node_left_hander' => $left_node,
                            'node_right_hander' => $right_node,
                            'node_level' => $node_level
                        );
                        DB::table('d_17')->where('district_id', $data->district_id)->update($up_data);


                        if ($next_level == 'province' && ($flow_node_level == 3 && $flow_level == 'city')
                            && $flag_father !== 0 && $flag_grand_father !== 0)
                        {
                            //如果本节点下一个节点是省级的话,并且上一级是市级
                            //更新市级右节点，更新省级右节点

                            $father_right_node = $right_node + 1;//$flag_father;
                            DB::table('d_17')->where('district_id', $flag_father)->update(['node_right_hander' => $father_right_node]);


                            $grand_father_node = $right_node + 2;//$flag_grand_father
                            DB::table('d_17')->where('district_id', $flag_grand_father)->update(['node_right_hander' => $grand_father_node]);
                        }
                        if ($next_level == 'province' && ($flow_node_level == 2 && $flow_level == 'province')
                            && $flag_grand_father !== 0)
                        {
                            //如果本节点下一个节点是省级的话,并且上一级是省级
                            //更新省级右节点
                            $grand_father_node = $right_node + 1;//$flag_grand_father
                            DB::table('d_17')->where('district_id', $flag_grand_father)->update(['node_right_hander' => $grand_father_node]);
                        }

                        if($next_level == 'city' && $flag_father !== 0)
                        {
                            //若下一级节点是 市级
                            //更新市级的右节点
                            $father_right_node = $right_node + 1;//$flag_father;
                            DB::table('d_17')->where('district_id', $flag_father)->update(['node_right_hander' => $father_right_node]);
                        }
                    }

                    if($flow_level == 'district' || $flow_level == 'biz_area')
                    {
                        if($flow_node_level == 3 && ($flow_level == 'district' || $flow_level == 'biz_area'))
                        {
                            //如果该区不是省或者市下面第一个区
                            //如果该区 上一个节点是区，下一个节点也是区（区节点左值 = 上一个区节点右值 + 1）
                            $left_node = $is_first_district[0]->node_right_hander + 1;
                            $data->node_left_hander = $left_node;
                            //区右节点 = 区左节点 + 1
                            $right_node = $left_node + 1;
                            $data->node_right_hander = $right_node;

                            if ($flow_node_level == 4)
                            {
                                $node_level = 4;
                            }
                            if ($flow_node_level == 3)
                            {
                                $node_level = 3;
                            }
                            $up_data = array(
                                'node_left_hander' => $left_node,
                                'node_right_hander' => $right_node,
                                'node_level' => $node_level
                            );
                            DB::table('d_17')->where('district_id', $data->district_id)->update($up_data);

                            if ($next_level == 'province' && $flow_node_level == 3
                               && $flag_grand_father !== 0)
                            {
                                //如果本节点下一个节点是省级的话,并且上一级是市级
                                //更新省级右节点
                                $grand_father_node = $right_node + 1;//$flag_grand_father
                                DB::table('d_17')->where('district_id', $flag_grand_father)->update(['node_right_hander' => $grand_father_node]);
                            }

                        }
                        if($flow_node_level == 4 && ($flow_level == 'district' || $flow_level == 'biz_area'))
                        {
                            //如果该区不是省或者市下面第一个区
                            //如果该区 上一个节点是区，下一个节点也是区（区节点左值 = 上一个区节点右值 + 1）
                            $left_node = $is_first_district[0]->node_right_hander + 1;
                            $data->node_left_hander = $left_node;
                            //区右节点 = 区左节点 + 1
                            $right_node = $left_node + 1;
                            $data->node_right_hander = $right_node;

                            if ($flow_node_level == 4)
                            {
                                $node_level = 4;
                            }
                            if ($flow_node_level == 3)
                            {
                                $node_level = 3;
                            }
                            $up_data = array(
                                'node_left_hander' => $left_node,
                                'node_right_hander' => $right_node,
                                'node_level' => $node_level
                            );
                            DB::table('d_17')->where('district_id', $data->district_id)->update($up_data);

                            if ($next_level == 'province' && $flow_node_level == 4
                                && $flag_father !== 0 && $flag_grand_father !== 0)
                            {
                                //如果本节点下一个节点是省级的话,并且上一级是市级
                                //更新市级右节点，更新省级右节点

                                $father_right_node = $right_node + 1;//$flag_father;
                                DB::table('d_17')->where('district_id', $flag_father)->update(['node_right_hander' => $father_right_node]);


                                $grand_father_node = $right_node + 2;//$flag_grand_father
                                DB::table('d_17')->where('district_id', $flag_grand_father)->update(['node_right_hander' => $grand_father_node]);
                            }

                            if($next_level == 'city' && $flag_father !== 0)
                            {
                                //若下一级节点是 市级
                                //更新市级的右节点
                                $father_right_node = $right_node + 1;//$flag_father;
                                DB::table('d_17')->where('district_id', $flag_father)->update(['node_right_hander' => $father_right_node]);
                            }
                        }
                    }

                    if($data->district_id ==3271 && $flag_father !== 0 && $flag_grand_father !==0)
                    {
                        //如果这是最后一次循环
                        //（区节点左值 = 上一个区节点右值 + 1）（市节点右值 = 该区节点右值 + 1）
                        //（省节点右值 = 该区节点右值 + 2）（根节点右值 = 该区右节点值 + 3）
                        $left_node = $is_first_district[0]->node_right_hander + 1;
                        $data->node_left_hander = $left_node;
                        //区右节点 = 区左节点 + 1
                        $right_node = $left_node + 1;
                        $data->node_right_hander = $right_node;
                        $data->node_level = 3;
                        $up_data = array(
                            'node_left_hander'=>$left_node,
                            'node_right_hander'=>$right_node,
                            'node_level'=>3
                        );
                        DB::table('d_17')->where('district_id',$data->district_id)->update($up_data);

                        $grand_father_node = $right_node + 1;//$flag_grand_father
                        $orging_node = $right_node + 2;//$flag_grand_father
                        DB::table('d_17')->where('district_id',$flag_grand_father)->update(['node_right_hander'=>$grand_father_node]);
                        DB::table('d_17')->where('district_id',1)->update(['node_right_hander'=>$orging_node,'node_level'=>1]);
                    }
                }
            }
            else
            {
                continue;
            }
        }
    }
?>
package com.example.englishvocab.repository;

import com.example.englishvocab.entity.QuizResult;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface QuizResultRepository extends JpaRepository<QuizResult, Integer> {
    List<QuizResult> findByUser_UserIDOrderByTakenAtDesc(Integer userId);
}
